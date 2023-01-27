<?php
	$hrtime_start = hrtime();
	$NANOSEC = 1E9;

	/**
	 * Calculate process runtime as [s, ns].
	 */
	function hrtime_interval() {
		global $hrtime_start, $NANOSEC;
		list($s, $ns) = hrtime();
		list($s0, $ns0) = $hrtime_start;
		// Borrow
		if ($ns < $ns0) { $s--; $ns += $NANOSEC; }
		return [$s - $s0, $ns - $ns0];
	}

	function runtime_str() {
		list($s, $ns) = hrtime_interval();
		return
			date('i:s.', $s) .
			str_pad(intdiv($ns, 1E6), 3, "0", STR_PAD_LEFT);
	}

	function log_info($msg) {
		fwrite(STDERR, "[" . runtime_str() . "] [i] $msg" . PHP_EOL);
	}
?>