<?php

namespace SSLCreator;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use SSLCreator\GenerateCommand;

class GenerateApplication extends Application {

    protected function getCommandName(InputInterface $input) {

        return 'generate';

    }

    protected function getDefaultCommands() {

        $defaultCommands = parent::getDefaultCommands();

        $defaultCommands[] = new GenerateCommand();

        return $defaultCommands;

    }

    public function getDefinition() {

        $inputDefinition = parent::getDefinition();
        $inputDefinition->setArguments();
        return $inputDefinition;

    }

}