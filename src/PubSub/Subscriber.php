<?php
namespace PubSub;

interface Subscriber {
	public function receiveMessage(Message $message);
}