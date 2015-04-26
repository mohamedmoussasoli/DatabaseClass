<?php
    
    class Database {
        
        private $conn; // database connection object
        private $dsn = "mysql:host=localhost;dbname=database";
        private $username = "root";
        private $password = "";
        private $tableName;
        
        private $query; //database query object
        
        public function __construct ($table_name){ // when the object is created connect to the database automatically using the the connection function
            $this->tableName = $table_name;
            $this->connect($this->dsn);
        }
        
        private function connect ($dsn) { // database connection function
            try{
                $this->conn = new PDO ($dsn,$this->username,$this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
            }catch(PDOException $e) {
                die ($e->getMessage);
            }
        }
        
        public function query ($sql) { // sql using query method
            $this->query = $this->conn->query($sql);
            return $this->query;
        }
        
        public function exec ($sql) { // sql using exec method
            $this->query = $this->conn->exec($sql);
            return $this->query;
        }
        
        public function prepare ($sql) { // sql using prepare method
            $this->query = $this->conn->prepare($sql);
            return $this->query;
        }
        
        public function bindParam ($placeholder,$value,$param_constant = null) { //bindParam method for prepare statement
            $this->query->bindParam($placeholder,$value,$param_constant);
        }
        
        public function bindValue ($placeholder,$value,$param_constant = null) { //bindValue method for prepare statement
            $this->query->bindValue($placeholder,$value,$param_constant);
        }
        
        public function bindValues ($data) {
            
            foreach($data as $key=>$value) {
                if(is_string($value)) {
                    $this->bindValue(":".$key,$this->escape($value),PDO::PARAM_STR);
                }elseif(is_numeric($value)) {
                    $this->bindValue(":".$key,$this->escape($value),PDO::PARAM_INT);
                }elseif(is_bool($value)) {
                    $this->bindValue(":".$key,$this->escape($value),PDO::PARAM_BOOL);
                }
            }
            
        }

        /**
         * @param $output the value to escape
         * @return string the escaped value
         */

        public function escape ($output) {
            $output = trim($output);
            $output = stripcslashes($output);
            $output = htmlentities($output);
            return $output;
        }
        
        public function bindColumn ($column,$var) { //bindColumn method to assign column value to a variable 
            $this->query->bindColumn($column,$var);
        }
            
        public function execute () { // execute method for prepare method
            $this->query->execute();
        }
        
        public function quickPrepare ($sql,$array = array()) {
            $this->query = $this->conn->prepare($sql);
            $this->query->execute($array);
        }
        
        /*public function insert ($table_name,$data){    
            $keys = array_keys($data);
            $keys = implode(',',$keys);
            $values = array_values($data);
            foreach($values as $value) {
                if(is_numeric($value)) {
                    $values2[]= $this->escape($value);
                }else {
                    $values2[] = "'".$this->escape($value)."'";
                }
            }
            $values2 = implode(",",$values2);
            
            $query = $conn->query("insert into ".$table_name." (".$keys.") values (".$values2.")");
        }*/

        function insert ($data){
        
            $keys = array_keys($data);
            $values = array_values($data);
            
            $keys_string = implode(',',$keys);
            foreach($keys as $key) {
                $keys_prepare[] = ":".$key;
            }
            $keys_prepare = implode(',',$keys_prepare);
            
            $this->prepare("insert into ".$this->tableName." (".$keys_string.") values (".$keys_prepare.")");
        
            $this->bindValues($data);
            
            $this->execute();
        }
        
        public function basicSelect ($data = null,$options = array("*")) {
            
            $options = implode(',',$options);
            
            if (isset($data)) {
                
                $where = " WHERE ";
                $end = 1;
                foreach($data as $key=>$value) {
                    $count = count($data);
                    if($count > 1) {
                        if($end  == ($count)) {
                            $where .= $key."=:".$key;
                            break;
                        }
                            $where .= $key."=:".$key." AND ";
                            $end++;
                    }else{
                        $where .= $key."=:".$key;
                    }
                }
              
            }else{
                $where = null;    
            }
            
            $this->prepare("SELECT ".$options." FROM ".$this->tableName.$where);
            
            if(isset($data)) $this->bindValues($data);
            
            $this->execute();
            
            if($this->rowCount() == 1) $result = $this->fetch();
            elseif($this->rowCount() > 1) $result = $this->fetchAll();
            
            return $result;
            
        }
        
        function basicUpdate ($set_info,$where_info) {
            
            $set = " SET ";
            $count = count($set_info);
            $end = 1;
            
            foreach($set_info as $key=>$value) {
                if($count > 1) {
                    if($end == $count) {
                        $set .= $key."=:".$key;
                        break;
                    }
                    $set .= $key."=:".$key." , ";
                    $end++;
                }else{
                    $set .= $key."=:".$key;
                }
            }
            
            $where = " WHERE ";
            $count_where = count($where_info);
            $end_where = 1;
            
            foreach($where_info as $key=>$value) {
                if($count_where > 1) {
                    if($end_where  == ($count_where)) {
                        $where .= $key."=:".$key;
                        break;
                    }
                        $where .= $key."=:".$key." AND ";
                        $end++;
                }else{
                    $where .= $key."=:".$key;
                }
            }
            
            $this->prepare("UPDATE ".$this->tableName.$set.$where);
            
            $this->bindValues($set_info);
            $this->bindValues($where_info);
            
            $this->execute();
        }
        
        public function selectRow ($sql,$array) { // selecting row from a table
            $this->quick_prepare($sql,$array);
            return $this->fetch();
        }
        
        public function rowCount () { // return number of affected rows 
            return $this->query->rowCount();
        }
        
        public function lastInsertId(){ // return the last inserted id 
            return $this->conn->lastInsertId();
        }       
        
        public function fetch ($fetch_method = PDO::FETCH_ASSOC) { // fetch a row from a table based on (pdo fetch method)
            return $this->query->fetch($fetch_method);
        }
        
        public function fetchColumn ($index) { // fetch a column from a table
            return $this->query->fetchColumn($index);
        }
        
        public function fetchAll ($fetch_all_method = PDO::FETCH_ASSOC) { // fetching all data from database
            return $this->query->fetchAll($fetch_all_method);  
        }
        
        
        
        public function close () { // close connection
            $this->conn = null;
        }
        
    }
    


    
    
    
    
?>