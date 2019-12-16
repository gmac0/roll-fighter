<?php
namespace PubSub;

class Broker {
	use \Log;

	private $subscribers = [];

	public function publish(Message $message) {
		$this->log("Broker subscribers:", 'debug');
		foreach ($this->subscribers as $name => $filteredSubscribers) {
			foreach ($filteredSubscribers as $filteredSubscriber) {
				$this->log("  $name " . get_class($filteredSubscriber), 'debug');
			}
		}

		if (!isset($this->subscribers[$message->name])) {
			$this->log("no subscibers for {$message->name}", 'warn');
			return;
		}
		foreach ($this->subscribers[$message->name] as $filteredSubscriber) {
			 $filteredSubscriber->receiveMessage($message);
		}
	}

	public function subscribe($messageName, Subscriber $subscriber) {
		if (!isset($this->subscribers[$messageName])) {
			$this->subscribers[$messageName] = [];
		}
		$this->subscribers[$messageName][] = $subscriber;
	}

	public function cleanup($object) {
		foreach ($this->subscribers as $name => $filteredSubscribers) {
			foreach ($filteredSubscribers as $index => $filteredSubscriber) {
				if ($object === $filteredSubscriber) {
					unset($this->subscribers[$name][$index]);
				}
			}
		}
	}

}