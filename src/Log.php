<?php

trait Log {
	public static $priority = 2;

	private $priorityMap = [
		'debug' => 0,
		'info' => 1,
		'notice' => 2,
		'warn' => 3,
	];
	
	private function log($msg, $priority = 'notice') {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		$traceStr = '';
		$trace = array_reverse($trace);
		array_pop($trace);
		foreach ($trace as $call) {
			$type = isset($call['type']) ? $call['type'] : '';
			$source = isset($call['class']) ? $call['class'] : basename($call['file']);
			$traceStr .= "\n  {$type}{$source}::{$call['function']}()";
		}
		if ($this->priorityMap[$priority] >= self::$priority) {
			fwrite(STDERR, "$msg. Trace: $traceStr". PHP_EOL);
		}
	}
}