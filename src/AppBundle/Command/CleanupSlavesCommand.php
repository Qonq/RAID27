<?php


namespace AppBundle\Command;

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



        $this->output->writeln("<info>END CONSOLE COMMAND</info>");
    }

    private function cleanup(){
        foreach ($this->slaves as $slave){
            $slaveDirStructure = $this->getDirContents($slave['root']);

            foreach ($slaveDirStructure as $item){

                if ($item['isDir'])
                    continue;
                $this->checkMaster($item, $slave, false);
            }

            foreach ($slaveDirStructure as $item){

                if (!$item['isDir'])
                    continue;
                $this->checkMaster($item, $slave, true);

            }


            $this->output->writeln("<info>FINISHED CLEANUP FOR SLAVE:".$slave['name'].".</info>");

        }


    }




    /**
     * Returns the Contents of the master disk as the return of linux find . command does as an array
     *
     * @param $dir     string
     * @param $results array recursive call
     *
     * @return array
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
     * Checks if file is exists on master and if the MD5sum is equal, if not, it deletes file or directory
     *
     * @param array $item
     * @param array $slave
     * @param bool $isDir
     */
    public function checkMaster($item, $slave, $isDir){

        $slaveRoot =    $slave['root'];
        $path =         $item['path'];
        $checkPath =    $this->master['root'].substr($path, strlen($slaveRoot));


        if ($isDir){
            if(file_exists($checkPath) && is_dir($checkPath)){
                return;
            }
            rmdir($path);

        }else{
            if (file_exists($checkPath) && is_file($checkPath)){
                $md5Tocompare = md5_file($path);
                $md5Master    = md5_file($checkPath);
                if ($md5Tocompare == $md5Master){
                    return;
                }
            }
            unlink($path);
        }
        return;
    }

}