SSL Creator
===========

A tool for creating self signed multi-domain (and wildcard) SSL certificates for the purposes of development! For production you should use certificates verified by a third party.

Introduction
------------

Normally you would have remember and do this of all on the command line:

```sh
openssl genrsa -out multidomain.key 2048
openssl req -new -key multidomain.key -out multidomain.csr
echo "subjectAltName=DNS:first.com,DNS:*.second.com,DNS:another.io,IP:10.0.0.0" > domain_extensions
openssl x509 -req -in multidomain.csr -signkey multidomain.key -extfile domain_extensions -out multidomain.crt -days 10000
rm multidomain.csr domain_extensions
```

That's difficult. So instead we use PHP, and we don't even need openssl. It works on Windows computers too!

Installation
------------

```sh
composer require polycademy/sslcreator:*
```

Usage
-----

Doing this:

```sh
sslcreator primary.com secondary.com *.wildcard.com -f multidomain -b 2048
```

Results in this:

```sh
Registering these domains:
    - primary.com
    - secondary.com
    - *.wildcard.com
Generating Key Pair
Generating Certificate Signing Request
Signing the Certificate
Saving Key and Certificate at Current Working Directory
Saved as:
    - ./multidomain.key
    - ./multidomain.crt
```

Need a lot of domains? Just point to a JSON file.

```sh
sslcreator -j ./domains.json -f multidomain -b 1024
```

The JSON file needs to be:

```json
[
    "primarydomain.com",
    "anotherdomain.com",
    "*.wildcard.com"
]
```

Help
----

```sh
sslcreator -h
```
