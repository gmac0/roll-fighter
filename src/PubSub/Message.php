<?php
namespace PubSub;

class Message {
	public $name;
	public $data;

	public function __construct($name, $data = null) {
		$this->name = $name;
		$this->data = $data;
	}
}
