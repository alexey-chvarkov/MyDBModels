<?php



class DBConfig
{
	public static $Server = "localhost";
	public static $User = "root";
	public static $Password = "кщще";
	public static $Name = "database";
}


class DBConnection
{

	private $Connection;

	private $UseMysqli;

	public function __construct()
	{
		$this->Connection = mysqli_connect(DBConfig::$Server, DBConfig::$User, DBConfig::$Password) or die ("Not connect");
		mysqli_select_db($this->Connection, DBConfig::$Name) or die ("Not db");
	}

	public function query($sql)
	{
		$res = mysqli_query($this->Connection, $sql);
		if (is_bool($res))
			return $res;
		while ($row = mysqli_fetch_assoc($res)) {
			$out[] = $row;
		}
		return $out;
	}

}






class DB
{

	public $Users;

	private $Connection;

	public function __construct()
	{
		$this->Connection = new DBConnection();
		$this->Users = new UserCollection($this->Connection);
	}

	public function query($sql)
	{
		return mysqli_query($this->Connection, $sql);
	}

}







abstract class Object
{
	protected $Type;

	protected function __construct($Type="Object")
	{
		$this->Type = $Type;
	}

	public function getType()
	{
		return $this->Type;
	}

	abstract public function toString();

	abstract public function isEmpty();

	abstract public function equal($object);

}







class User extends Object
{

	public $UserId;
	public $Login;
	public $Password;
	public $Age;
	public $About;
	public $DateReg;

	protected function setType($Type)
	{
		parent::__construct($Type);
	}

	public function __construct($UserId, $Login, $Password, $Age, $About = null, $DateReg = null)
	{
		parent::__construct("User");
		$this->UserId = $UserId;
		$this->Login = $Login;
		$this->Password = $Password;
		$this->Age = $Age;
		$this->About = $About;
		$this->DateReg = $DateReg;
	}

	public function toString()
	{
		return "{
			\"Type\": \"$this->Type\",
			\"UserId\": $this->UserId,
			\"Login\": \"$this->Login\",
			\"Password\": \"$this->Password\",
			\"Age\": \"$this->Age\",
			\"About\": \"$this->About\"
		}";
	}

	public function isEmpty()
	{
		return (!$this->UserId && !$this->Login && !$this->Password 
			&& !$this->Age && !$this->About);
	}

	public function equal($object)
	{
		return ($this == $object);
	}
}








class UserRow extends User
{

	private $Connection;

	public function __set($name, $value)
	{
		if (!$this->Connection)
		{
			$Connection->query("UPDATE `Users` SET `$name` = '$value' WHERE `UserId` = ".$this->UserId);
		}
		else
			return null;
	}

	public function __get($name)
	{
		if (!$this->Connection)
		{
			$Connection = new DBConnection();
			$get = $Connection->query("SELECT `$name` FROM `Users` WHERE 
				`UserId` = $this->UserId and 
				`Login` = '$this->Login' and
				`Password` = '$this->Password' and
				`Age` = $this->Age and
				`About` = '$this->About' and
				`DateReg` = '$this->DateReg' 
				");
			return $get[0][$name];
		}
		else
			return null;

	}

	public function setConnection($Connection)
	{
		$this->Connection = $Connection;
	}

	public function remove()
	{
		if (!$this->Connection)
		{
			try
			{
				$Connection = new DBConnection();
				$remove = $Connection->query("DELETE FROM `Users` WHERE 
					`UserId` = $this->UserId and 
					`Login` = '$this->Login' and
					`Password` = '$this->Password' and
					`Age` = $this->Age and
					`About` = '$this->About' and
					`DateReg` = '$this->DateReg' 
					");
				return $remove;
			}
			catch (Exception $e)
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	public function __construct($Connection, $User=null)
	{
		
		
		$this->setConnection($Connection);
		
		if ($User)
		{
			parent::__construct($User->UserId, $User->Login, $User->Password, $User->Age, $User->About, $User->DateReg);
		}
		$this->setType("UserRow");
	}

	public function getValue()
	{
		return new User($this->UserId, $this->Login, $this->Password, $this->Age, $this->About, $this->DateReg);
	}


	public function toString()
	{
		$value = $this;
		return "{
			\"Type\": \"$value->Type\",
			\"UserId\": $value->UserId,
			\"Login\": \"$value->Login\",
			\"Password\": \"$value->Password\",
			\"Age\": \"$value->Age\",
			\"About\": \"$value->About\"
		}";
	}

	public function isEmpty()
	{
		return (!$this->UserId && !$this->Login && !$this->Password 
			&& !$this->Age && !$this->About);
	}

	public function equal($object)
	{
		return ($this == $object);
	}
}




class CollectionIterator implements Iterator
{
    private $var = array();

    public function __construct($array)
    {
        if (is_array($array)) {
            $this->var = $array;
        }
    }

    public function rewind()
    {
        reset($this->var);
    }
  
    public function current()
    {
        $var = current($this->var);
        return $var;
    }
  
    public function key() 
    {
        $var = key($this->var);
        return $var;
    }
  
    public function next() 
    {
        $var = next($this->var);
        return $var;
    }
  
    public function valid()
    {
        $key = key($this->var);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

}



class Collection extends Object implements IteratorAggregate, ArrayAccess, Countable
{

	protected $Type;

	protected $Collection;

	private $offset = 0;


	public function __construct($Type, $Data = array())
	{
		$this->Type = $Type;
		foreach ($Data as $value) {
			$this->add(new UserRow($this->Connection, $value));
		}
	}

	private function isThisType($Type)
	{
		return ($this->Type == $Type);
	}

	public function getType()
	{
		return $this->Type."Collection";
	}

	public function add($object)
	{
		if ($object != null && $this->isThisType($object->getType()))
		{
			$this->Collection[] = $object;	
		}
		return new Collection("User", $this->Collection);
	}

	public function clear()
	{
		$this->Collection = array();
	}

	public function toArray()
	{
		return $this->Collection;
	}

	public function first()
	{
		return ($this->count() >= 1)? $this->Collection[0] : null;
	}

	public function last()
	{
		$count = $this->count();
		return ($count >= 1)? $this->Collection[$count - 1] : null;
	}

	private function __sort($arg1, $arg2, $sorted)
	{
		return ($arg1->$sorted >= $arg2->$sorted)? 1 : -1;
	}

	public function sort()
	{
		$arr = $this->Collection;
		ussort($arr, "__sort");
		return new Collection($this->Type, $this->Collection);
	}

	public function where()
	{

	}

	public function toString()
	{
		$responce = "\"".$this->Type."Collection\": [ ";
		foreach ($this->Collection as $value) {
			$responce .= $value->toString();
			if (next($this->Collection))
				$responce .= ", ";
		}
		return $responce." ]";
	}

	public function isEmpty()
	{
		return ($this->count == 0);
	}

	public function equal($object)
	{
		return ($this == $object);
	}

	public function getIterator()
	{
		return new CollectionIterator($this->Collection);
	}


	public function offsetExists($offset)
    {
        return isset($this->Collection[$offset]);
    }

    public function offsetGet($offset)
    {
        return ($this->offsetExists($offset)) ? $this->Collection[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->Collection[] = $value;
        }
        else {
            $this->Collection[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->Collection[$offset]);
    }


    public function count()
    {
    	return sizeof($this->Collection);
    }


}



class UserCollection extends Collection
{
	private $Connection;

	public function __construct($Connection)
	{
		parent::__construct("UserRow");
		$this->Connection = $Connection;
		$res = $this->Connection->query("SELECT * FROM `Users`");
		foreach ($res as $value) {
			$this->add(new UserRow($this->Connection, new User($value["UserId"], $value["Login"], $value["Password"], $value["Age"], $value["About"], $value["DateReg"])));
		}
	}

}




