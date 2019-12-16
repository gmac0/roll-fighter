<?php
/*
	Enforces the existance of the receiveMessage function for any
	classes wishing to subscribe to PubSub messages.
*/

namespace PubSub;

interface Subscriber {
	public function receiveMessage(Message $message);
}