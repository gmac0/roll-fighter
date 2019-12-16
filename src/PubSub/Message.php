<?php
/*
	Wrapper for sending structured data through the PubSub system
*/

namespace PubSub;

class Message {
	public $name;
	public $data;

	public function __construct($name, $data = null) {
		$this->name = $name;
		$this->data = $data;
	}
}
