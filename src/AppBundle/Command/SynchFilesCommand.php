<?php


namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SynchFilesCommand extends Command
{
    protected function configure(){

        $this
            ->setName('raid27:synch:start')
            ->setDescription("Envokes the synchronisation process");

    }

    protected function execute(InputInterface $input, OutputInterface $output){


        $output->write("<info>SUCCESS</info>");

    }
}