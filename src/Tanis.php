<?php

namespace Avlas\Tanis;

class Tanis
{
    private static $logDate = "";
    private static $logName = "";

    private static $emailLog = [];

    public static function greet() : string {
        return 'Ciao da Tanis! :)'; 
    }

    public static function date(string $date) : Tanis {
        self::$logDate = $date;

        return new self();
    }

    public static function name(string $name) : Tanis {
        self::$logName = $name;

        return new self();
    }

    public static function sendReport(){
        $report = self::getReport();

        $email = self::getReportEmail($report);

        self::sendEmail($email, self::config()['users'], 'Daily Log Report');
    }

    private static function config() : array { 
        $configPath = __DIR__ . '../config.php';

        if (!file_exists($configPath)) {
            throw new \RuntimeException("Configuration file not found: $configPath");
        }

        return include $configPath; 
    }

    private static function logDir() : string { 
        return self::config()['log_path']; 
    }

    private static function getLogFiles() : array {
        $allFiles = array_diff(scandir(self::config()['log_path']), ['.', '..']);
        $files = [];

        foreach($allFiles as $file) {
            $matchesFileName = self::$logName == "" || str_contains($file, self::$logName);
            $matchesDate = self::$logDate == "" || str_contains($file, self::$logDate);

            if ($matchesFileName && $matchesDate) {
                $files[] = $file;
            }
        }

        if (empty($files)) {
            throw new \RuntimeException("No matching log files found for name: " . self::$logName . " and date: " . self::$logDate);
        }

        return $files;
    }

    private static function extractLogDetails(string $logLine) : array|null {
        $pattern = '/\[(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)/';
        
        if (preg_match($pattern, $logLine, $matches)) {
            return [
                'date' => $matches[1],
                'time' => $matches[2],
                'environment' => $matches[3],
                'severity' => $matches[4],
                'message' => $matches[5]
            ];
        } else {
            error_log("Invalid log line format: $logLine");
            return null;
        }

        return null;
    }

    private static function getReport() : string {
        $report = [];
        
        $files = self::getLogFiles();

        foreach ($files as $file) {
            $fh = fopen(self::logDir().'/'.$file, 'r');
            
            $report[$file]['messageCount'] = 0;
            while($logLine = fgets($fh)){
                $report[$file]['messageCount']++;
                $logLineDetails = self::extractLogDetails($logLine);

                if(isset($logLineDetails['date']) && !isset($report[$file]['date'])){
                    $report[$file]['date'] = $logLineDetails['date'];
                }

                if (isset($logLineDetails['severity'])) {
                    if(self::config()['severity_levels'][$logLineDetails['severity']]['explicit'] === true){
                        $report[$file][$logLineDetails['severity']][] = $logLine;
                    }

                    if(self::config()['severity_levels'][$logLineDetails['severity']]['emergency'] === true){
                        self::sendEmail(self::getEmergencyEmail($logLine), self::config()['users'], '⚠️ Emergency Log Alert ⚠️');
                    }

                    if (isset($report[$file]['messages'][$logLineDetails['severity']])) {
                        $report[$file]['messages'][$logLineDetails['severity']]++;
                    } else {
                        $report[$file]['messages'][$logLineDetails['severity']] = 1;
                    }
                } else {
                    echo "errore riga di log non valida: " . $logLine;
                    continue;
                }
            }

            fclose($fh);
        }

        return json_encode($report);
    }

    private static function getEmergencyEmail(string $logLine) : array {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

        $message = "<h2 style='color: darkred;'>! Emergency Log Alert !</h2>";
        
        $message .= "<p>Un messaggio di <strong style='color: darkred;'>EMERGENZA</strong> è stato individuato nei log:</p>";
        
        $message .= "<table style='font-family: Arial, sans-serif; border-collapse: collapse; width: 100%;'>";
        $message .= "<thead>
                        <tr>
                            <th style='text-align:left; padding: 8px;'>Log Entry</th>
                        </tr>
                     </thead>
                     <tbody>";
        
        $message .= "<tr>
                        <td style='padding: 12px; font-weight: bold; color: darkred;'>
                            $logLine
                        </td>
                     </tr>";
        
        $message .= "</tbody></table>";
                
        return [
            'headers' => $headers,
            'message' => $message,
        ];
    }    

    private static function getReportEmail(string $reports) : array {
        $reports = json_decode($reports, true);

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

        $totalMessages = [
            'Overall' => 0,
        ];
        foreach(self::config()['severity_levels'] as $severity_level => $details){
            $totalMessages[$severity_level] = 0;
        }
        foreach($reports as $fileName => $report){
            foreach ($report['messages'] as $severity => $count) {
                if(isset($totalMessages[$severity])){
                    $totalMessages[$severity] += $count;
                }
            }
            $totalMessages['Overall'] += $report['messageCount'];
        }

        $message = "<h2>Report " . self::$logDate . " " . self::$logName . ":</h2>";

        $message .= "<h3>Riepilogo Totale</h3>";
        $message .= "<table style='font-family: Arial, sans-serif; border-collapse: collapse; width: 100%;'>";
        $message .= "<tbody>";
        foreach($totalMessages as $severity_level => $count){
            if(self::config()['severity_levels'][$severity_level]['bold'] == true){
                $message .= 
                "<tr>
                    <td style='padding: 8px; font-weight: bold;'>Totale $severity_level</td>
                    <td style='padding: 8px; font-weight: bold;'>{$count}</td>
                </tr>";
            }else{
                $message .= 
                "<tr>
                    <td style='padding: 8px;'>Totale $severity_level</td>
                    <td style='padding: 8px;'>{$count}</td>
                </tr>";
            }
        }
        $message .= "</tbody>";
        $message .= "</table>";

        foreach($reports as $fileName => $report){
            $message .= "<hr style='border: 1px solid #ccc; margin: 20px 0;'>";

            $message .= "<h3>File: {$fileName}</h3>";
            $message .= "<table style='font-family: Arial, sans-serif; border-collapse: collapse; width: 100%;'>";
            $message .= 
                "<thead>
                    <tr>
                        <th style='text-align:left; padding: 8px;'>Gravità</th>
                        <th style='text-align:left; padding: 8px;'>Numero</th>
                    </tr>
                </thead>
            <tbody>";

            foreach ($report['messages'] as $severity => $count) {
                if(self::config()['severity_levels'][$severity]['bold'] == true){
                    $message .= "<tr style='font-weight: bold;'>
                                    <td style='padding: 8px;'>$severity</td>
                                    <td style='padding: 8px;'>$count</td>
                                </tr>";
                } else {
                    $message .= "<tr>
                                    <td style='padding: 8px;'>$severity</td>
                                    <td style='padding: 8px;'>$count</td>
                                </tr>";
                }
            }

            $message .= "</tbody></table>";

            foreach(self::config()['severity_levels'] as $severity => $details){
                if($details['explicit'] === true){
                    if (!empty($report[$severity])) {
                        $message .= "<h4>$severity nel file {$fileName}:</h4>";
                        $message .= "<ul>";

                        $printedMessages = [];
                        
                        foreach ($report[$severity] as $logLine) {
                            if (isset($printedMessages[$logLine])) {
                                $printedMessages[$logLine]++;
                            } else {
                                $printedMessages[$logLine] = 1;
                            }
                        }

                        foreach ($printedMessages as $logLine => $count) {
                            if (strlen($logLine) > 100) {
                                $logLine = substr($logLine, 0, 100) . '...';
                            }

                            if ($count > 1) {
                                $message .= "<br>";
                            }

                            $message .= "<li style='color: " . $details['html_color'] . ";'>";
                            $message .= "$logLine";
                            
                            if ($count > 1) {
                                $message .= "<div style='font-weight: bold; margin-top: 10px;'>Ripetuto $count volte</div>";
                                $message .= "<br>";
                            }
                        
                            $message .= "</li>";
                        }

                        $message .= "</ul>";
                    }
                }
            }
        }

        return [
            'headers' => $headers, 
            'message' => $message
        ];
    }

    private static function canSendEmail($userEmail) : bool {
        $timeNow = new \DateTime();
        $minuteAgo = (clone $timeNow)->modify('-1 minute');
        $hourAgo = (clone $timeNow)->modify('-1 hour');
        $dayAgo = (clone $timeNow)->modify('-1 day');

        $emailsInLastMinute = array_filter(self::$emailLog[$userEmail] ?? [], fn($time) => new \DateTime($time) >= $minuteAgo);
        $emailsInLastHour = array_filter(self::$emailLog[$userEmail] ?? [], fn($time) => new \DateTime($time) >= $hourAgo);
        $emailsInLastDay = array_filter(self::$emailLog[$userEmail] ?? [], fn($time) => new \DateTime($time) >= $dayAgo);

        if (count($emailsInLastMinute) >= self::config()['max_emails_per_minute']) {
            throw new \RuntimeException("Email limit reached per minute for user: $userEmail");
        }
        if (count($emailsInLastHour) >= self::config()['max_emails_per_hour']) {
            throw new \RuntimeException("Email limit reached per hour for user: $userEmail");
        }
        if (count($emailsInLastDay) >= self::config()['max_emails_per_day']) {
            throw new \RuntimeException("Email limit reached per day for user: $userEmail");
        }

        return true;
    }

    private static function logEmailSent($userEmail) {
        $timeNow = (new \DateTime())->format('Y-m-d H:i:s');

        if (!isset(self::$emailLog[$userEmail])) {
            self::$emailLog[$userEmail] = [];
        }

        self::$emailLog[$userEmail][] = $timeNow;
    }

    private static function sendEmail(array $email, array $users, string $subject){
        foreach($users as $user){
            try{
                if (!filter_var($user, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid user email: $user");
                }

                if (!self::canSendEmail($user)) {
                    throw new \RuntimeException("Email sending limit reached for: $user");
                }

                if (!mail($user, $subject, $email['message'], $email['headers'])) {
                    throw new \Exception("Failed to send email to: $user");
                }

                self::logEmailSent($user);

            }catch(Exception $e){
                error_log("Error in sendEmail: " . $e->getMessage());
                echo 'Error sending email. Please check error log.';
            }
        }
    }
}