# crevasse/converter [![Build Status](https://travis-ci.org/crevasse/converter.svg?branch=master)](https://travis-ci.org/crevasse/converter) [![Coverage Status](https://coveralls.io/repos/github/crevasse/converter/badge.svg?branch=master)](https://coveralls.io/github/crevasse/converter?branch=master) [![codecov](https://codecov.io/gh/crevasse/converter/branch/master/graph/badge.svg)](https://codecov.io/gh/crevasse/converter)
Simple `hash-json` creation for any project managed via crevasse.

It takes your existing project's `default.conf`.

* Create a single converts hash-json file, including its all.
* Automated convert process
* Zero additional configuration

**Table of contents**

* [Usage](#usage)
  * [crevasse](#crevasse)
  * [crevasse install](#install)
  * [crevasse convert](#simple-convert)
* [Install](#install)
  * [As a phar (recommended)](#as-a-phar-recommended)
  * [Updating crevasse](#updating-crevasse)
* [License](#license)

## Usage

Once crevasse/converter is [installed](#install), you can use it via command line like this.

###  simple convert

This tool supports several sub-commands. To get you started, you can now use the following simple command:

```bash
$ crevasse convert
```

This will actually execute the convert command, that allows you take from `*.conf` to fast convert to `convert.json` file
(see below description of the [convert command](#convert-convert) for more details).

### convert command

The convert command provides an fast command line convert. 
if you not specify conf path, it an convert via default conf path. 
So if you know the exact conf path, 
you can use the following command:

```bash
$ crevasse convert default.conf export convert.json
```
## Install

You can grab a copy of crevasse/converter in either of the following ways.

### As a phar (recommended)

You can simply download a pre-compiled and ready-to-use version as a Phar
to any directory.
Simply download the latest `crevasse.phar` file from our
[releases page](https://github.com/crevasse/converter/releases):

[Latest release](https://github.com/crevasse/converter/releases/latest)

That's it already. You can now verify everything works by running this:

```bash
$ cd ~/Downloads
$ php crevasse.phar -v
```

The above usage examples assume you've installed crevasse system-wide to your $PATH (recommended),
so you have the following options:

1.  Only use crevasse locally and adjust the usage examples: So instead of

    running `$ crevasse -v`, you have to type `$ php crevasse.phar -v`.


3.  Or you can manually make the `crevasse.phar` executable and move it to your $PATH by running:

   ```bash
   $ chmod 755 crevasse.phar
   $ sudo mv crevasse.phar /usr/local/bin/crevasse
   ```
 
If you have installed phar-composer system-wide, you can now verify everything works by running:

```bash
$ crevasse -v
```

#### Updating crevasse

There's no separate `update` procedure, simply download the latest release again
and overwrite the existing phar.

## License

MIT
