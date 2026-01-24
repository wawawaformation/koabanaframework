<?php

declare(strict_types=1);

namespace Koabana\Database;

use Dba\Connection;



class BDDFactory
{
    /**
     * Connexion PDO mémorisée par l'instance.
     */
    protected ?MyPDO $connection = null;

   
    public function __construct() {}

   
    public function getConnection(): MyPDO
    {


      
            $dsn = getenv('DB_DSN');
            $user = getenv('DB_USER') ?: '';
            $password = getenv('DB_PASSWORD') ?: '';


            

           

            if($dsn === 'sqlite::memory:') {
                $user = null;
                $password = null;
            }

           
            if(!$dsn || $dsn === '') {
                throw new \RuntimeException('DSN de la base de données non configuré.');
            }
      

        if($this->connection === null)
        {
          
            $this->connection = new MyPDO($dsn, $user, $password);   
        }


        

        return $this->connection;
       
    }  
    
}
