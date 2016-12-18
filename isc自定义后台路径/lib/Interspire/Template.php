<?php

class Interspire_Template_Filesystem extends Twig_Loader_Filesystem
{
	protected $patterns = array();
    public function __construct($paths = array(), $rootPath = null)
    {
		parent::__construct($paths, $rootPath);
    }
	
    public function setSearchAndReplace($patterns = array())
    {
		$this->patterns = $patterns;
    }
	
    public function doSearchAndReplace($content)
    {
		if (!empty($this->patterns)) {
			foreach($this->patterns as $search => $replace){
				$content = str_replace($search, $replace, $content);
			}
		}
		return $content;
    }
	
    /**
     * {@inheritdoc}
     */
    public function getSource($name)
    {
		$content = parent::getSource($name);

		return $this->doSearchAndReplace($content);
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceContext($name)
    {
        $path = $this->findTemplate($name);

		$content = $this->doSearchAndReplace(file_get_contents($path));
		
        return new Twig_Source($content, $name, $path);
    }	
}

class Interspire_Template extends Twig_Environment
{
    private static $instances = array();

    private $assignedVars = array();

    public function __construct($templatePaths, array $options = array())
    {
        if (!is_array($templatePaths)) {
            $templatePaths = array($templatePaths);
        }

        $options = array_merge(array(
            'auto_escape' => true,
        ), $options);

        $options['debug'] = GetConfig('DebugMode');
		
		//要替换的字符
		$replacePatterns = array(
			'/admin/' => '/'.ISC_XRAY_NAME.'/',
			'admin/' => ISC_XRAY_NAME.'/',
			'/index.php?' => '/?'
		);
		
		if (defined('ISC_ADMIN_MODERN')) {
			$replacePatterns = array(
				'/admin/' => '/modern/',
				'admin/' => 'modern/',
				'/index.php?' => '/?'
			);
		}
		
		if (ISC_XRAY_NAME == 'admin') {
			$loader = new Twig_Loader_Filesystem($templatePaths);
		} else {
	        $loader = new Interspire_Template_Filesystem($templatePaths);
			$loader->setSearchAndReplace($replacePatterns);
		}


        parent::__construct($loader, $options);

        // override twig's escaper extension with ours
        $this->addExtension(new Interspire_Template_Extension_Escaper((bool) $options['auto_escape']));

        $this->addExtension(new Interspire_Template_Extension());
    }

    /**
     * Get a named instance of the template system (e.g. 'admin').
     *
     * @param string       $instance
     * @param array|string $templatePaths
     * @param array        $options
     *
     * @return Interspire_Template
     */
    public static function getInstance($instance, $templatePaths = null, $options = array())
    {
        if (empty(self::$instances[$instance])) {
            self::$instances[$instance] = new self($templatePaths, $options);
        }

        return self::$instances[$instance];
    }

    public function assign($name, $value)
    {
        $this->assignedVars[$name] = $value;

        return $this;
    }

    public function getAssignedVars()
    {
        return $this->assignedVars;
    }

    public function render($template, array $context = array())
    {
        $template = $this->loadTemplate($template);

        return $template->render($this->assignedVars + $context + $GLOBALS);
    }

    public function display($template, array $context = array())
    {
        $template = $this->loadTemplate($template);
        $template->display($this->assignedVars + $context + $GLOBALS);
    }

    public function getCacheFilename($name)
    {
        // this method is the old behvaiour, before Twig core changed to use sub-directories
        return $this->getCache() ? $this->getCache().'/'.$this->getTemplateClass($name).'.php' : false;
    }

    public function clearCacheFiles()
    {
        // this method is the old behvaiour, before Twig core changed to use sub-directories
        if ($this->cache) {
            foreach (new DirectoryIterator($this->cache) as $fileInfo) {
                if (0 === strpos($fileInfo->getFilename(), $this->templateClassPrefix)) {
                    @unlink($fileInfo->getPathname());
                }
            }
        }
    }
}
