<?php


namespace Myddleware\InstallBundle\Entity;


class DatabaseParameters
{
    /**
	* @var string
    *
    */
    private $database_driver;

     /**
	* @var string
    *
    */
    private $database_host;

    /**
	* @var integer
    *
    */
    private $database_port;

    /**
	* @var string
    *
    */
    private $database_name;

    /**
	* @var string
    *
    */
    private $database_user;

    /**
	* @var string
    *
    */
    private $database_password;

    /**
	* @var string
    *
    */
    private $secret;

    /**
	* @var integer
    *
    */
    private $myddleware_support;

    /**
	* @var array
    *
    */
    private $param;

    /**
	* @var array
    *
    */
    private $extension_allowed;

    /**
	* @var string
    *
    */
    private $myd_version;

    /**
	* @var integer
    *
    */
    private $block_install;


    /**
     * Get database_driver
     *
     * @return string 
     */
    public function getDatabaseDriver()
    {
        return $this->database_driver;
    }

      /**
     * Get database_host
     *
     * @return string 
     */
    public function getDatabaseHost()
    {
        return $this->database_host;
    }

    /**
     * Get database_name
     *
     * @return string 
     */
    public function getDatabaseName()
    {
        return $this->database_name;
    }

    /**
     * Get database_port
     *
     * @return integer
     */
    public function getDatabasePort()
    {
        return $this->database_port;
    }

    /**
     * Get database_user
     *
     * @return string 
     */
    public function getDatabaseUser()
    {
        return $this->database_user;
    }

    /**
     * Get database_password
     *
     * @return string 
     */
    public function getDatabasePassword()
    {
        return $this->database_password;
    }

    /**
     * Get secret
     *
     * @return string 
     */
    public function getSecret()
    {
        return $this->secret;
    }


    /**
     * Get myddleware_support
     *
     * @return integer
     */
    public function getMyddlewareSupport()
    {
        return $this->myddleware_support;
    }

    /**
     * Get param
     *
     * @return array
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * Get extension_allowed
     *
     * @return array
     */
    public function getExtensionAllowed()
    {
        return $this->extension_allowed;
    }

    /**
     * Get myd_version
     *
     * @return string
     */
    public function getMydVersion()
    {
        return $this->myd_version;
    }

    /**
     * Get block_install
     *
     * @return string
     */
    public function getBlockInstall()
    {
        return $this->block_install;
    }


    /**
     * Set database_driver
     *
     * @param string $database_driver
     * @return DatabaseParameters
     */
    public function setDatabaseDriver($database_driver)
    {
        $this->database_driver = $database_driver;

        return $this;
    }

     /**
     * Set database_host
     *
     * @param string $database_host
     * @return DatabaseParameters
     */
    public function setDatabaseHost($database_host)
    {

        $this->database_host = $database_host;

        return $this;
    }

    /**
     * Set database_port
     *
     * @param integer $database_port
     * @return DatabaseParameters
     */
    public function setDatabasePort($database_port)
    {
        
        $this->database_port = $database_port;

        return $this;
    }

    /**
     * Set database_name
     *
     * @param string $database_name
     * @return DatabaseParameters
     */
    public function setDatabaseName($database_name)
    {
        
        $this->database_name = $database_name;

        return $this;
    }

    /**
     * Set database_user
     *
     * @param string $database_user
     * @return DatabaseParameters
     */
    public function setDatabaseUser($database_user)
    {
        
        $this->database_user = $database_user;

        return $this;
    }

    /**
     * Set database_password
     *
     * @param string $database_password
     * @return DatabaseParameters
     */
    public function setDatabasePassword($database_password)
    {
        
        $this->database_password = $database_password;

        return $this;
    }

    /**
     * Set secret
     *
     * @param string $secret
     * @return DatabaseParameters
     */
    public function setSecret($secret)
    {
        
        $this->secret = $secret;

        return $this;
    }

    /**
     * Set myddleware_support
     *
     * @param integer $myddleware_support
     * @return DatabaseParameters
     */
    public function setMyddlewareSupport($myddleware_support)
    {
        
        $this->myddleware_support = $myddleware_support;

        return $this;
    }

    /**
     * Set param
     *
     * @param array $param
     * @return DatabaseParameters
     */
    public function setParam($param)
    {
        
        $this->param = $param;

        return $this;
    }

    /**
     * Set extension_allowed
     *
     * @param array $extension_allowed
     * @return DatabaseParameters
     */
    public function setExtensionAllowed($extension_allowed)
    {
        
        $this->extension_allowed = $extension_allowed;

        return $this;
    }

    /**
     * Set myd_version
     *
     * @param string $myd_version
     * @return DatabaseParameters
     */
    public function setMydVersion($myd_version)
    {
        
        $this->myd_version = $myd_version;

        return $this;
    }

    /**
     * Set block_install
     *
     * @param integer $block_install
     * @return DatabaseParameters
     */
    public function setBlockInstall($block_install)
    {
        
        $this->block_install = $block_install;

        return $this;
    }
}
