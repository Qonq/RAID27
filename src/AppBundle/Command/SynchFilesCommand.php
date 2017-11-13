<?php


namespace AppBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

class SynchFilesCommand extends Command
{

    private $disks;

    public function __construct($disks = array()) {

        $this->disks = $disks;

        parent::__construct();
    }

    protected function configure(){

        $this
            ->setName('raid27:synch:start')
            ->setDescription("Envokes the synchronisation process");

    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $output->writeln("<info>STARTING CONSOLE COMMAND</info>");


        $path = realpath("/media/abdullah/ElementsEXT4/RAID/source/");



        $dirs = [];
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        foreach($objects as $name => $object){
            $dirs[$name] = $object;
        }


        print_r($dirs);




        //$dirs = $this->scandir_rec("/media/abdullah/ElementsEXT4/RAID/source/");
        //
        //$output->writeln($dirs);


        $output->writeln("<info>\nEND CONSOLE COMMAND</info>");
    }


    protected function get(){

    }

    protected function scandir_rec($root){


        // When it's a file or not a valid dir name
        // Print it out and stop recusion
        if (is_file($root) || !is_dir($root)) {
            return;
        }

        // starts the scan
        $dirs = scandir($root);
        foreach ($dirs as $dir) {
            if ($dir == '.' || $dir == '..') {
                continue; // skip . and ..
            }

            $path = $root . '/' . $dir;
            $this->scandir_rec($path); // <--- CALL THE FUNCTION ITSELF TO DO THE SAME THING WITH SUB DIRS OR FILES.
        }
        return $dirs;
    }


}