<?php

namespace Consolidation\Cgr;

/**
 * Hold a command string + envrionment to execute.
 */
class CommandToExec
{
    protected $command;
    protected $arguments;
    protected $env;
    protected $dir;

    /**
     * Hold some command values to later exec
     */
    public function __construct($command, $arguments, $env = array(), $dir = '')
    {
        $this->command = $command;
        $this->arguments = $arguments;
        $this->env = new Env($env);
        $this->dir = $dir;
    }

    /**
     * Generate a single command string.
     */
    public function getCommandString()
    {
        $escapedArgs = array_map(function ($item) {
            return escapeshellarg($item);
        }, $this->arguments);
        return $this->command . ' ' . implode(' ', $escapedArgs);
    }

    /**
     * Run our command. Set up the environment, as needed, ensuring that
     * it is restored at the end of the run.
     */
    public function run($stdoutFile = '')
    {
        $commandString = $this->getCommandString();
        $origEnv = $this->env->apply($this->env);
        $origDir = FileSystemUtils::applyDir($this->dir);
        $exitCode = static::runCommand($commandString, $stdoutFile);
        $origEnv->apply();
        FileSystemUtils::applyDir($origDir);
        return $exitCode;
    }

    /**
     * Run a single command.
     *
     * @param string $commandString
     * @return integer
     */
    public static function runCommand($commandString, $stdoutFile = '')
    {
        $stdout = STDOUT;
        $stderr = STDERR;
        if (!empty($stdoutFile)) {
            $stdout = array("file", $stdoutFile, "a");
            $stderr = $stdout;
        }
        $process = proc_open($commandString, array(0 => STDIN, 1 => $stdout, 2 => $stderr), $pipes);
        $procStatus = proc_get_status($process);
        $exitCode = proc_close($process);
        return ($procStatus["running"] ? $exitCode : $procStatus["exitcode"]);
    }
}
