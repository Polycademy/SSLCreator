<?php

namespace SSLCreator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Crypt_RSA;
use File_X509;

class GenerateCommand extends Command {

    protected function configure () {

        $this
            ->setName('generate')
            ->setDescription('Generate SSL key and certificate with multiple domains for development')
            ->addArgument(
                'domains',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Domains you want to use. Separate multiple domains with a space.',
                array()
            )
            ->addOption(
                'filename',
                'f',
                InputOption::VALUE_REQUIRED,
                'Filename of key and certificate without extension.',
                'random'
            )
            ->addOption(
                'domainsJson',
                'j',
                InputOption::VALUE_REQUIRED,
                'Path to JSON file containing an array of domains for the certificate.',
                realpath(__DIR__ . '/../resources/domains.json')
            )
            ->addOption(
                'bits',
                'b',
                InputOption::VALUE_REQUIRED,
                'Bit size',
                2048
            )
        ;

    }

    protected function execute (InputInterface $input, OutputInterface $output) {

        $cwd = getcwd();

        $domains = $input->getArgument('domains');

        $filename = $input->getOption('filename');

        $domainsPath = $input->getOption('domainsJson');

        $bitsize = $input->getOption('bits');

        if (empty($domains)) {

            $output->writeLn('<info>No domains were passed in, therefore acquiring domains from JSON file</info>');

            try {
                $domains = $this->parse_json($domainsPath);
            }catch (\Exception $e) {
                $output->writeln('ERROR: ' . $e->getMessage());
                return;
            }

        }

        $outputString = "<comment>Registering these domains</comment>:";
        foreach ($domains as $domain) {
            $outputString .= "\n    - $domain";
        }
        $output->writeLn($outputString);

        $output->writeLn('<info>Generating Key Pair</info>');

        $privateKey = new Crypt_RSA;
        $publicKey = new Crypt_RSA;

        $keyPair = $privateKey->createKey((integer) $bitsize);

        $privateKey->loadKey($keyPair['privatekey']);
        $publicKey->loadKey($keyPair['publickey']);

        $output->writeLn('<info>Generating Certificate Signing Request</info>');

        $csr = new File_X509;
        $csr->setPublicKey($publicKey);
        call_user_func_array(array($csr, 'setDomain'), $domains);

        $issuer = new File_X509;
        $issuer->setPrivateKey($privateKey);
        $issuer->setDN($csr->getDN());

        $output->writeLn('<info>Signing the Certificate</info>');

        $certificate = new File_X509;
        $certificate->setEndDate('lifetime');
        $signed_certificate = $certificate->sign($issuer, $csr);

        $output->writeLn('<info>Saving Key and Certificate at Current Working Directory</info>');

        $keyPath = "$cwd/$filename.key";
        $certPath = "$cwd/$filename.crt";
        file_put_contents($keyPath, $privateKey->getPrivateKey());
        file_put_contents($certPath, $certificate->saveX509($signed_certificate));

        $output->writeLn("<comment>Saved as:</comment>\n    - $keyPath\n    - $certPath");

    }

    protected function parse_json($json_file) {

        if(is_file($json_file) AND is_readable($json_file)){
            $data = file_get_contents($json_file);
        }else{
            throw new \Exception("The $json_file file could not be found or could not be read.");
        }

        $data = json_decode($data, true);

        switch(json_last_error()){
            case JSON_ERROR_DEPTH:
                $error = "The $json_file file exceeded maximum stack depth.";
            break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = "The $json_file file hit an underflow or the mods mismatched.";
            break;
            case JSON_ERROR_CTRL_CHAR:
                $error = "The $json_file file has an unexpected control character.";
            break;
            case JSON_ERROR_SYNTAX:
                $error = "The $json_file file has a syntax error, it\'s JSON is malformed.";
            break;
            case JSON_ERROR_UTF8:
                $error = "The $json_file file has malformed UTF-8 characters, it could be incorrectly encoded.";
            break;
            case JSON_ERROR_NONE:
            default:
                $error = '';
        }

        if(!empty($error)){
            throw new \Exception($error);
        }

        return $data;

    }

}