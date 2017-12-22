<?php


namespace AppBundle\Command;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupSlavesCommand extends Command
{

    /** @var array $disks */
    private $disks;
    /** @var  OutputInterface $output */
    private $output;
    /** @var array $master */
    private $master;
    /** @var array $slaves */
    private $slaves;
    /** @var array $masterDirStructure */
    private $masterDirStructure;


    public function __construct($disks = array()) {
        parent::__construct();
        $this->disks = $disks;
    }

    protected function configure(){

        $this
            ->setName('raid27:cleanup:start')
            ->setDescription("Envokes the synchronisation process");

    }

    private function setMasterSlaves(){


        $this->slaves = [];

        foreach ($this->disks as $value){
            if ($value['type']== 'master'){
                $this->master = $value;
            }else{
                array_push($this->slaves, $value);
            }
        }

    }



    protected function execute(InputInterface $input, OutputInterface $output){

        $this->output = $output;
        $this->output->writeln("<info>STARTING CONSOLE COMMAND</info>");

        $this->setMasterSlaves();
        $this->masterDirStructure = $this->getDirContents($this->master['root']);


        $this->cleanup();


        $this->createDirStructure();
        $this->replicateFiles();

        $this->output->writeln("<info>END CONSOLE COMMAND</info>");
    }

    private function cleanup(){
        foreach ($this->slaves as $slave){
            $slaveDirStructure = $this->getDirContents($slave['root']);

            $itemCounter=0;
            foreach ($this->masterDirStructure as $item){
                if ($item['isDir']){
                    $itemCounter++;
                    continue;
                }

                if (file_exists($slaveDirStructure[$itemCounter]['path']) && is_file($slaveDirStructure[$itemCounter]['path'])){
                    $masterFileMd5 = md5_file($item['path']);
                    $slaveFileMd5 = md5_file($slaveDirStructure[$itemCounter]['path']);

                    if ($masterFileMd5 !== $slaveFileMd5){
                        unlink($slaveDirStructure[$itemCounter]['path']);
                    }
                }
                $itemCounter++;
            }


        }
    }


    /**
     * Returns the Contents of the master disk as the return of linux find . command does as an array
     *
     * @param $dir     string
     * @param $results array recursive call
     *
     * @return array $results array
     */
    private function getDirContents($dir, &$results = array()){
        if (is_dir($dir)){

            $files = scandir($dir);
            foreach($files as  $value){
                $path = realpath($dir.DIRECTORY_SEPARATOR.$value);
                if(!is_dir($path)) {
                    $array = array("isDir"=> false , "path" => $path );
                    $results[] = $array;
                } else if($value != "." && $value != "..") {
                    $this->getDirContents($path, $results);
                    $array = array("isDir"=> true , "path" => $path );
                    $results[] = $array;
                }
            }
        }
        return $results;
    }

    /**
     * Loops through the master disks files and replicates the directory structure on the slave disks
     */
    private function createDirStructure(){

        foreach ($this->slaves as $slave){

            foreach ($this->dirStructure as $item) {

                if (!$item['isDir']) {
                    continue;
                }
                $path = str_replace($this->master['root'],
                                    $slave['root'],
                                    $item['path']);

                if (!file_exists($path)) {
                    mkdir($path);
                    $this->output->writeln("<info>Directory: $path is successfully created on slave: " . $slave['name'] . ".</info>");
                } else {
                    if (!is_dir($path)) {
                        throw new Exception("Directory on master disk is present on the slave disk as a file for directory: $path");
                    }
                }
            }
            $this->output->writeln("<comment>Directories successfully replicated on slave: ". $slave['name'] . " </comment>");
        }
        $this->output->writeln("<info>Directories successfully replicated for all slaves.</info>");
    }


    /**
     * Loops through the master disks files and replicates the directory structure on the slave disks
     */
    private function replicateFiles(){

        foreach ($this->slaves as $slave){
            foreach ($this->masterDirStructure as $item) {

                if ($item['isDir']) {
                    continue;
                }
                $path = str_replace($this->master['root'],
                                    $slave['root'],
                                    $item['path']);

                if (!file_exists($path)) {
                    copy($item['path'], $path);
                    $this->output->writeln("<info>File: $path is successfully created on slave: " .$slave['name'] . ".</info>");
                } else {
                    $existngMd5 = md5_file($path);
                    $masterFileMd5 = md5_file($item['path']);

                    if ($existngMd5 == $masterFileMd5) {
                        continue;
                    }else{
                        $this->output->writeln("<error>File: $path already exists on slave: ". $slave['name'] . "!</error>");
                    }
                }
            }
            $this->output->writeln("<comment>Files successfully replicated on slave: ".$slave['name'] ." </comment>");
        }
        $this->output->writeln("<info>All files are successfully replicated for all slaves </info>");
    }



}