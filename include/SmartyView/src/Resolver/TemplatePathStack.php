<?php
namespace SmartyView\Resolver;

use SmartyView\Exception;
use SplFileInfo;
use Zend\View\Resolver\ResolverInterface;
use Zend\Stdlib\SplStack;

class TemplatePathStack implements ResolverInterface
{
    const FAILURE_NO_PATHS  = 'TemplatePathStack_Failure_No_Paths';
    const FAILURE_NOT_FOUND = 'TemplatePathStack_Failure_Not_Found';
    
    /**
     * Default suffix to use
     *
     * Appends this suffix if the template requested does not use it.
     *
     * @var string
     */
    protected $defaultSuffix = 'tpl';
    
    /**
     * Reason for last lookup failure
     *
     * @var false|string
     */
    protected $lastLookupFailure = false;
    
    /**
     * Flag indicating whether or not LFI protection for rendering view scripts is enabled
     * @var bool
     */
    protected $lfiProtectionOn = true;
    
    /**
     * @var SplStack
     */
    protected $paths;
    
    /**
     * Add a single path to the stack
     *
     * @param  string $path
     * @return TemplatePathStack
     * @throws Exception\InvalidArgumentException
     */
    public function addPath($path)
    {
        if (!is_string($path)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid path provided; must be a string, received %s',
                gettype($path)
            ));
        }
        $this->paths[] = static::normalizePath($path);
        return $this;
    }
    
    /**
     * Add many paths to the stack at once
     *
     * @param  array $paths
     * @return TemplatePathStack
     */
    public function addPaths(array $paths)
    {
        foreach ($paths as $path) {
            $this->addPath($path);
        }
        return $this;
    }
    
    /**
     * Clear all paths
     *
     * @return void
     */
    public function clearPaths()
    {
        $this->paths = new SplStack;
    }
    
    /**
     * Get default file suffix
     *
     * @return string
     */
    public function getDefaultSuffix()
    {
        return $this->defaultSuffix;
    }
    
    /**
     * Get the last lookup failure message, if any
     *
     * @return false|string
     */
    public function getLastLookupFailure()
    {
        return $this->lastLookupFailure;
    }
    
    /**
     * Returns stack of paths
     *
     * @return SplStack
     */
    public function getPaths()
    {
        return $this->paths;
    }
    
    /**
     * Return status of LFI protection flag
     *
     * @return bool
     */
    public function isLfiProtectionOn()
    {
        return $this->lfiProtectionOn;
    }
    
    /**
     * Normalize a path for insertion in the stack
     *
     * @param  string $path
     * @return string
     */
    public static function normalizePath($path)
    {
        $path = rtrim($path, '/');
        $path = rtrim($path, '\\');
        $path .= DIRECTORY_SEPARATOR;
        return $path;
    }
    
    /**
     * Resolve a template/pattern name to a resource the renderer can consume
     *
     * @param  string $name
     * @param  null|Renderer $renderer
     * @return mixed
     */
    public function resolve($name, Renderer $renderer = null)
    {
        $this->lastLookupFailure = false;
        
        if ($this->isLfiProtectionOn() && preg_match('#\.\.[\\\/]#', $name)) {
            throw new Exception\DomainException(
                'Requested scripts may not include parent directory traversal ("../", "..\\" notation)'
            );
        }
        
        if (!count($this->paths)) {
            $this->lastLookupFailure = static::FAILURE_NO_PATHS;
            return false;
        }
        
        // Ensure we have the expected file extension
        $defaultSuffix = $this->getDefaultSuffix();
        if (pathinfo($name, PATHINFO_EXTENSION) == '') {
            $name .= '.' . $defaultSuffix;
        }
        
        foreach ($this->paths as $path) {
            $file = new SplFileInfo($path . $name);
            if ($file->isReadable()) {
                // Found! Return it.
                if (($filePath = $file->getRealPath()) === false && substr($path, 0, 7) === 'phar://') {
                    // Do not try to expand phar paths (realpath + phars == fail)
                    $filePath = $path . $name;
                    if (!file_exists($filePath)) {
                        break;
                    }
                }
                return $filePath;
            }
        }
        
        $this->lastLookupFailure = static::FAILURE_NOT_FOUND;
        return false;
    }
    
    /**
     * Set default file suffix
     *
     * @param  string $defaultSuffix
     * @return TemplatePathStack
     */
    public function setDefaultSuffix($defaultSuffix)
    {
        $this->defaultSuffix = (string) $defaultSuffix;
        $this->defaultSuffix = ltrim($this->defaultSuffix, '.');
        return $this;
    }
    
    /**
     * Set LFI protection flag
     *
     * @param  bool $flag
     * @return TemplatePathStack
     */
    public function setLfiProtection($flag)
    {
        $this->lfiProtectionOn = (bool) $flag;
        return $this;
    }
    
    /**
     * Rest the path stack to the paths provided
     *
     * @param  SplStack|array $paths
     * @return TemplatePathStack
     * @throws Exception\InvalidArgumentException
     */
    public function setPaths($paths)
    {
        if ($paths instanceof SplStack) {
            $this->paths = $paths;
        } elseif (is_array($paths)) {
            $this->clearPaths();
            $this->addPaths($paths);
        } else {
            throw new Exception\InvalidArgumentException(
                "Invalid argument provided for \$paths, expecting either an array or SplStack object"
            );
        }
    
        return $this;
    }
}