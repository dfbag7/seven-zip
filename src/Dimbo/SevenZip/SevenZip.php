<?php namespace Dimbo\SevenZip;

use Symfony\Component\Process\Process;

class SevenZip
{
    const COMMAND_ADD = 'a';
    const COMMAND_EXTRACT = 'e';
    const COMMAND_EXTRACT_WITH_PATHS = 'x';

    const SFX_FOR_INSTALLERS = '7zSD.sfx';

    const SEVEN_ZIP_EXECUTABLE = '7z.exe';

    /** @var  string */
    protected $pathToBinary;

    /** @var  \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var  string */
    protected $command;

    /** @var array */
    protected $options = [];

    /** @var  string */
    protected $sfxName;

    /** @var  string */
    protected $outputFile;

    /** @var array  */
    protected $inputFiles = [];

    public function __construct($logger)
    {
        $this->pathToBinary = __DIR__ . '\..\..\..\bin\\';

        $this->logger = $logger;
    }

    /**
     * @param string $command
     *
     * @return $this
     */
    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @param string $outputFile
     *
     * @return $this
     */
    public function setOutputFile($outputFile)
    {
        $this->outputFile = $outputFile;

        return $this;
    }

    /**
     * @param string $inputFile
     *
     * @return $this
     */
    public function addInputFile($inputFile)
    {
        $this->inputFiles[] = $inputFile;

        return $this;
    }

    /**
     * @param string $sfxName
     *
     * @return $this
     */
    public function setSfxName($sfxName)
    {
        $this->sfxName = $sfxName;

        return $this;
    }

    /**
     * @param string $optionName
     * @param string|null $value
     *
     * @return $this
     */
    public function addOption($optionName, $value = null)
    {
        $this->options[$optionName] = $value;

        return $this;
    }

    private static function appendArg(&$str, $arg)
    {
        if(!empty($arg))
        {
            if(strlen($str) >= 1 && substr($str, -1, 1) !== ' ')
            {
                $str .= ' ';
            }

            $str .= $arg;
        }
    }

    private function appendOneOption(&$str, $option, $value = null)
    {
        if(strlen($str) >= 1 && substr($str, -1, 1) !== ' ')
        {
            $str .= ' ';
        }

        $str .= $option;

        if($value !== null)
        {
            $str .= escapeshellarg($value);
        }
    }

    protected function constructOptions()
    {
        $result = '';

        if( !empty($this->sfxName) )
        {
            $result .= '-sfx' . $this->getFullPathToSfx();
        }

        foreach($this->options as $name => $value)
        {
            if(is_integer($name))
            {
                $this->appendOneOption($result, $value);
            }
            elseif(is_array($value))
            {
                foreach((array)$value as $oneValue)
                {
                    $this->appendOneOption($result, $name, $oneValue);
                }
            }
            else
            {
                $this->appendOneOption($result, $name, $value);
            }
        }

        return $result;
    }

    protected function constructInputFiles()
    {
        $resultArray = [];

        foreach((array)$this->inputFiles as $inputFile)
        {
            $resultArray[] = escapeshellarg($inputFile);
        }

        return join(' ', $resultArray);
    }

    protected function constructArgs()
    {
        $result = $this->command;

        static::appendArg($result, $this->constructOptions());
        static::appendArg($result, $this->outputFile);
        static::appendArg($result, $this->constructInputFiles());

        return $result;
    }

    /**
     * @return string
     */
    protected function getFullPathToSevenZipExecutable()
    {
        return $this->pathToBinary . self::SEVEN_ZIP_EXECUTABLE;
    }

    /**
     * @return string|null
     */
    protected function getFullPathToSfx()
    {
        if( !empty($this->sfxName) )
            return $this->pathToBinary . $this->sfxName;
    }

    protected function exec($command)
    {
        if($this->logger)
            $this->logger->info('Command started: ' . $command);

        $process = new Process($command);
        $process->run();

        if($this->logger)
        {
            $this->logger->info('Command ended: ' . $command);

            $stdOut = $process->getOutput();
            if(!empty($stdOut))
                $this->logger->debug('STDOUT: ' . $process->getOutput());

            $stdErr = $process->getErrorOutput();
            if(!empty($stdErr))
                $this->logger->debug('STDERR: ' . $stdErr);

            $this->logger->debug('Return code: ' . $process->getExitCode());
        }

        return $process->getExitCode();
    }

    protected function checkResultCode($resultCode)
    {
        if($resultCode > 1)
        {
            switch($resultCode)
            {
                case 2:
                    $errorMessage = 'Fatal error';
                    break;
                case 7:
                    $errorMessage = 'Command line error';
                    break;
                case 8:
                    $errorMessage = 'Not enough memory for operation';
                    break;
                case 255:
                    $errorMessage = 'User stopped the process';
                    break;
                default:
                    $errorMessage = 'Error code: ' . $resultCode;
            }

            throw new SevenZipError($resultCode, $errorMessage);
        }
    }

    public function clearParams()
    {
        $this->command = null;
        $this->options = [];
        $this->sfxName = [];
        $this->outputFile = null;
        $this->inputFiles = [];

        return $this;
    }

    public function run()
    {
        $command = escapeshellarg($this->getFullPathToSevenZipExecutable())
            . ' ' . $this->constructArgs();

        $resultCode = $this->exec($command);

        $this->checkResultCode($resultCode);

        $this->clearParams();

        return $resultCode;
    }
}
