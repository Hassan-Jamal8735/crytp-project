<?php
// In app/Console/Commands/RunArbitrageScan.php
$output = shell_exec('C:\Users\hassa\AppData\Local\Programs\Python\Python313\python.exe ' . base_path('app/Python/trading_simulator.py'));
$prices = json_decode($output, true);
