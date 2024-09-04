<<<<<<< HEAD
<?php
require "fpdf.php";

// IMPORTANT: You need to insert your OpenAI API Key here
define("myAPIKey", "YOUR_API_KEY"); // Insert your OpenAI API Key here

// Enable error reporting for debugging
ini_set('display_errors', 0); // Disable error display
error_reporting(E_ALL); // Report all PHP errors

// Function to log errors to a file
function logError($functionName, $errorMessage) {
    $errorLogFile = 'errorlog.txt';
    $currentTime = date("Y-m-d H:i:s");
    $logMessage = "[$currentTime] Error in $functionName: $errorMessage" . PHP_EOL;
    file_put_contents($errorLogFile, $logMessage, FILE_APPEND);
}

// Connect and create the SQLite database
try {
    $db = new PDO("sqlite:stories.db"); // Create a new SQLite database or open an existing one
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exception

    // Create the table if it does not exist
    $db->exec("CREATE TABLE IF NOT EXISTS stories (
        id INTEGER PRIMARY KEY,
        name TEXT,
        age INTEGER,
        gender TEXT,
        setting TEXT,
        friend TEXT,
        enemy TEXT,
        story TEXT,
        creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Log the error and return a JSON response
    logError("Database Connection", $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Database connection error."]);
    exit();
}

// Class to handle OpenAI API requests
class OpenAIRequest {
    private $apiKey;
    private $answer;
    private $error;
    private $errorCode;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->answer = "";
        $this->error = "";
        $this->errorCode = 0;
    }

    // Send a request to the OpenAI API
    public function sendRequest($prompt, $maxTokens, $temperature) {
        // Replace special characters to ensure the prompt is properly formatted
        $prompt = str_replace(
            ["\r", "\t", "\n", '"'],
            [" ", " ", " ", '\"'],
            $prompt
        );

        // Prepare data for API request
        $data = [
            "model" => "gpt-4o-mini",
            "max_tokens" => $maxTokens,
            "temperature" => $temperature,
            "messages" => [["role" => "user", "content" => $prompt]],
        ];

        $jsonData = json_encode($data);

        // Initialize cURL session
        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 30, // Set timeout to 30 seconds
            CURLOPT_CONNECTTIMEOUT => 15, // Set connection timeout to 15 seconds
        ]);

        // Execute cURL session
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Check for errors in the API response
        if ($httpCode != 200) {
            $this->errorCode = $httpCode;
            $this->error = "API returned HTTP code $httpCode";
            logError("OpenAIRequest::sendRequest", $this->error);
        } elseif ($response === false) {
            $this->errorCode = curl_errno($ch);
            $this->error = curl_error($ch);
            logError("OpenAIRequest::sendRequest", $this->error);
        } else {
            $decodedResponse = json_decode($response, true);
            if (isset($decodedResponse["choices"][0]["message"]["content"])) {
                $this->answer = $decodedResponse["choices"][0]["message"]["content"];
            } else {
                $this->errorCode = 1;
                $this->error = "Invalid API response";
                logError("OpenAIRequest::sendRequest", $this->error);
            }
        }

        curl_close($ch); // Close cURL session
    }

    public function getAnswer() {
        return $this->answer;
    }

    public function getError() {
        return $this->error;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }
}

// Custom PDF class extending FPDF for specific formatting
class PDF extends FPDF {
    // Page header
    function Header() {
        // Generate a random background image
        $randomNum = rand(1, 20);
        $formattedNumber = str_pad($randomNum, 2, "0", STR_PAD_LEFT);
        $backgroundImage = "./images/bg{$formattedNumber}.jpg";
        $this->Image($backgroundImage, 0, 0, 297, 210); // A4 landscape
    }

    // Page footer
    function Footer() {
        $this->SetY(-15); // Position at 1.5 cm from bottom
        $this->SetFont("Arial", "B", 14);
        $this->Cell(0, 12, $this->PageNo(), 0, 0, "R"); // Page number on the right
    }

    // Set text area for content
    function SetTextArea($x, $y, $width, $height) {
        $this->textArea = ["x" => $x, "y" => $y, "width" => $width, "height" => $height];
        $this->currentY = $y;
    }

    // Print a line within the defined text area
    function PrintLine($text) {
        if ($this->currentY + 10 > $this->textArea['y'] + $this->textArea['height']) {
            $this->AddPage(); // Add a new page if necessary
            $this->currentY = $this->textArea['y']; // Reset Y position
        }
        $this->SetXY($this->textArea["x"], $this->currentY);
        $this->Cell($this->textArea['width'], 10, $text, 0, 0, "J");
        $this->currentY += 10; // Increment Y position
    }

    // Print multiple lines within the defined text area
    function MultiCellInArea($width, $height, $text, $fontSize, $textColor) {
        $this->SetFont("Arial", "", $fontSize);
        $this->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $lines = explode("\n", $text . "\n");
        foreach ($lines as $line) {
            $testLine = "";
            $currentLine = "";
            $words = explode(" ", $line);
            foreach ($words as $word) {
                if (strlen($word) > 0) {
                    $testLine = $currentLine . " " . $word;
                    $lineWidth = $this->GetStringWidth($testLine);
                    if ($lineWidth > $width) {
                        $this->PrintLine($currentLine);
                        $currentLine = $word;
                    } else {
                        $currentLine = $testLine;
                    }
                }
            }
            if (strlen($currentLine) > 0) {
                $this->PrintLine($currentLine);
            }
            $this->currentY += 2; // Add space between lines
        }
    }
}

// Handling POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Data cleaning and validation
    $name = htmlspecialchars(trim($_POST["name"]));
    $age = filter_var($_POST["age"], FILTER_VALIDATE_INT);
    $language = htmlspecialchars(trim($_POST["language"]));
    $setting = htmlspecialchars(trim($_POST["setting"]));
    $friend = htmlspecialchars(trim($_POST["friend"]));
    $enemy = htmlspecialchars(trim($_POST["enemy"]));

    // Validate inputs
    if ($name && $age !== false && $age >= 0 && $age <= 12) {
        // Default protagonist
        $protagonist = "a child"; // Modify this as needed

        // Read localization file and get the prompt
        $localizationFile = 'localization.txt';
        $prompt = "";
        if (file_exists($localizationFile)) {
            $lines = file($localizationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                list($code, $langName, $template) = explode('|', $line);
                if ($code === $language) {
                    $prompt = $template;
                    break;
                }
            }
        }

        // Replace placeholders in the prompt
        $prompt = str_replace(['$name', '$age', '$setting', '$friend', '$enemy'], [$name, $age, $setting, $friend, $enemy], $prompt);

        // Call the OpenAI API
        $api = new OpenAIRequest(myAPIKey);
        $api->sendRequest($prompt, 1500, 0.7);
        $story = $api->getAnswer();

        if ($story) {
            // Convert text for PDF
            $story = iconv("UTF-8//IGNORE", "WINDOWS-1252//IGNORE", $story);
            $story = htmlspecialchars_decode($story, ENT_QUOTES);
            $lines = explode("\n", $story);
            $title = trim($lines[0]);
            $content = implode("\n", array_slice($lines, 1));

            // Save the story to SQLite
            try {
                $stmt = $db->prepare(
                    "INSERT INTO stories (name, age, gender, setting, friend, enemy, story) VALUES (:name, :age, :gender, :setting, :friend, :enemy, :story)"
                );
                $stmt->bindParam(":name", $name);
                $stmt->bindParam(":age", $age);
                $stmt->bindParam(":gender", $protagonist); // Update with actual gender
                $stmt->bindParam(":setting", $setting);
                $stmt->bindParam(":friend", $friend);
                $stmt->bindParam(":enemy", $enemy);
                $stmt->bindParam(":story", $story);
                $stmt->execute();
            } catch (PDOException $e) {
                logError("Saving to Database", $e->getMessage());
                echo json_encode(["status" => "error", "message" => "Error saving to database."]);
                exit();
            }

            // Generate and output the PDF directly to the browser
            try {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $title . '.pdf"');
                $pdf = new PDF("L", "mm", "A4");
                $pdf->AddPage();
                $pdf->SetXY(1, 11);
                $pdf->SetFont("Arial", "B", 28);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(0, 10, $title, 0, 1, "C");
                $pdf->Ln(10);
                $pdf->SetXY(0, 10);
                $pdf->SetFont("Arial", "B", 28);
                $pdf->SetTextColor(255, 255, 15);
                $pdf->Cell(0, 10, $title, 0, 1, "C");
                $pdf->Ln(10);
                $pdf->SetXY(0, 0);
                $x = 107;
                $y = 35;
                $width = 165;
                $height = 140;
                $textColor = [0, 0, 0];
                $pdf->SetTextArea($x, $y, $width, $height);
                $pdf->MultiCellInArea($width, $height, $content, 18, [0, 0, 0]);

                $pdf->Output("D", $title . ".pdf");
                exit();
            } catch (Exception $e) {
                logError("Generating PDF", $e->getMessage());
                echo json_encode(["status" => "error", "message" => "Error generating PDF."]);
                exit();
            }
        } else {
            logError("OpenAI API", $api->getError());
            echo json_encode(["status" => "error", "message" => "Story generation failed."]);
            exit();
        }
    } else {
        logError("Input Validation", "Invalid input data.");
        echo json_encode(["status" => "error", "message" => "Invalid input data."]);
        exit();
    }
} else {
    logError("Request Method", "Unsupported request method.");
    echo json_encode(["status" => "error", "message" => "Unsupported request method."]);
    exit();
}
?>
=======
<?php
require "fpdf.php";

// IMPORTANT: You need to insert your OpenAI API Key here
define("myAPIKey", "YOUR_API_KEY"); // Insert your OpenAI API Key here

// Enable error reporting for debugging
ini_set('display_errors', 0); // Disable error display
error_reporting(E_ALL); // Report all PHP errors

// Function to log errors to a file
function logError($functionName, $errorMessage) {
    $errorLogFile = 'errorlog.txt';
    $currentTime = date("Y-m-d H:i:s");
    $logMessage = "[$currentTime] Error in $functionName: $errorMessage" . PHP_EOL;
    file_put_contents($errorLogFile, $logMessage, FILE_APPEND);
}

// Connect and create the SQLite database
try {
    $db = new PDO("sqlite:stories.db"); // Create a new SQLite database or open an existing one
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exception

    // Create the table if it does not exist
    $db->exec("CREATE TABLE IF NOT EXISTS stories (
        id INTEGER PRIMARY KEY,
        name TEXT,
        age INTEGER,
        gender TEXT,
        setting TEXT,
        friend TEXT,
        enemy TEXT,
        story TEXT,
        creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Log the error and return a JSON response
    logError("Database Connection", $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Database connection error."]);
    exit();
}

// Class to handle OpenAI API requests
class OpenAIRequest {
    private $apiKey;
    private $answer;
    private $error;
    private $errorCode;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->answer = "";
        $this->error = "";
        $this->errorCode = 0;
    }

    // Send a request to the OpenAI API
    public function sendRequest($prompt, $maxTokens, $temperature) {
        // Replace special characters to ensure the prompt is properly formatted
        $prompt = str_replace(
            ["\r", "\t", "\n", '"'],
            [" ", " ", " ", '\"'],
            $prompt
        );

        // Prepare data for API request
        $data = [
            "model" => "gpt-4o-mini",
            "max_tokens" => $maxTokens,
            "temperature" => $temperature,
            "messages" => [["role" => "user", "content" => $prompt]],
        ];

        $jsonData = json_encode($data);

        // Initialize cURL session
        $ch = curl_init("https://api.openai.com/v1/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 30, // Set timeout to 30 seconds
            CURLOPT_CONNECTTIMEOUT => 15, // Set connection timeout to 15 seconds
        ]);

        // Execute cURL session
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Check for errors in the API response
        if ($httpCode != 200) {
            $this->errorCode = $httpCode;
            $this->error = "API returned HTTP code $httpCode";
            logError("OpenAIRequest::sendRequest", $this->error);
        } elseif ($response === false) {
            $this->errorCode = curl_errno($ch);
            $this->error = curl_error($ch);
            logError("OpenAIRequest::sendRequest", $this->error);
        } else {
            $decodedResponse = json_decode($response, true);
            if (isset($decodedResponse["choices"][0]["message"]["content"])) {
                $this->answer = $decodedResponse["choices"][0]["message"]["content"];
            } else {
                $this->errorCode = 1;
                $this->error = "Invalid API response";
                logError("OpenAIRequest::sendRequest", $this->error);
            }
        }

        curl_close($ch); // Close cURL session
    }

    public function getAnswer() {
        return $this->answer;
    }

    public function getError() {
        return $this->error;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }
}

// Custom PDF class extending FPDF for specific formatting
class PDF extends FPDF {
    // Page header
    function Header() {
        // Generate a random background image
        $randomNum = rand(1, 20);
        $formattedNumber = str_pad($randomNum, 2, "0", STR_PAD_LEFT);
        $backgroundImage = "./images/bg{$formattedNumber}.jpg";
        $this->Image($backgroundImage, 0, 0, 297, 210); // A4 landscape
    }

    // Page footer
    function Footer() {
        $this->SetY(-15); // Position at 1.5 cm from bottom
        $this->SetFont("Arial", "B", 14);
        $this->Cell(0, 12, $this->PageNo(), 0, 0, "R"); // Page number on the right
    }

    // Set text area for content
    function SetTextArea($x, $y, $width, $height) {
        $this->textArea = ["x" => $x, "y" => $y, "width" => $width, "height" => $height];
        $this->currentY = $y;
    }

    // Print a line within the defined text area
    function PrintLine($text) {
        if ($this->currentY + 10 > $this->textArea['y'] + $this->textArea['height']) {
            $this->AddPage(); // Add a new page if necessary
            $this->currentY = $this->textArea['y']; // Reset Y position
        }
        $this->SetXY($this->textArea["x"], $this->currentY);
        $this->Cell($this->textArea['width'], 10, $text, 0, 0, "J");
        $this->currentY += 10; // Increment Y position
    }

    // Print multiple lines within the defined text area
    function MultiCellInArea($width, $height, $text, $fontSize, $textColor) {
        $this->SetFont("Arial", "", $fontSize);
        $this->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $lines = explode("\n", $text . "\n");
        foreach ($lines as $line) {
            $testLine = "";
            $currentLine = "";
            $words = explode(" ", $line);
            foreach ($words as $word) {
                if (strlen($word) > 0) {
                    $testLine = $currentLine . " " . $word;
                    $lineWidth = $this->GetStringWidth($testLine);
                    if ($lineWidth > $width) {
                        $this->PrintLine($currentLine);
                        $currentLine = $word;
                    } else {
                        $currentLine = $testLine;
                    }
                }
            }
            if (strlen($currentLine) > 0) {
                $this->PrintLine($currentLine);
            }
            $this->currentY += 2; // Add space between lines
        }
    }
}

// Handling POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Data cleaning and validation
    $name = htmlspecialchars(trim($_POST["name"]));
    $age = filter_var($_POST["age"], FILTER_VALIDATE_INT);
    $language = htmlspecialchars(trim($_POST["language"]));
    $setting = htmlspecialchars(trim($_POST["setting"]));
    $friend = htmlspecialchars(trim($_POST["friend"]));
    $enemy = htmlspecialchars(trim($_POST["enemy"]));

    // Validate inputs
    if ($name && $age !== false && $age >= 0 && $age <= 12) {
        // Default protagonist
        $protagonist = "a child"; // Modify this as needed

        // Read localization file and get the prompt
        $localizationFile = 'localization.txt';
        $prompt = "";
        if (file_exists($localizationFile)) {
            $lines = file($localizationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                list($code, $langName, $template) = explode('|', $line);
                if ($code === $language) {
                    $prompt = $template;
                    break;
                }
            }
        }

        // Replace placeholders in the prompt
        $prompt = str_replace(['$name', '$age', '$setting', '$friend', '$enemy'], [$name, $age, $setting, $friend, $enemy], $prompt);

        // Call the OpenAI API
        $api = new OpenAIRequest(myAPIKey);
        $api->sendRequest($prompt, 1500, 0.7);
        $story = $api->getAnswer();

        if ($story) {
            // Convert text for PDF
            $story = iconv("UTF-8//IGNORE", "WINDOWS-1252//IGNORE", $story);
            $story = htmlspecialchars_decode($story, ENT_QUOTES);
            $lines = explode("\n", $story);
            $title = trim($lines[0]);
            $content = implode("\n", array_slice($lines, 1));

            // Save the story to SQLite
            try {
                $stmt = $db->prepare(
                    "INSERT INTO stories (name, age, gender, setting, friend, enemy, story) VALUES (:name, :age, :gender, :setting, :friend, :enemy, :story)"
                );
                $stmt->bindParam(":name", $name);
                $stmt->bindParam(":age", $age);
                $stmt->bindParam(":gender", $protagonist); // Update with actual gender
                $stmt->bindParam(":setting", $setting);
                $stmt->bindParam(":friend", $friend);
                $stmt->bindParam(":enemy", $enemy);
                $stmt->bindParam(":story", $story);
                $stmt->execute();
            } catch (PDOException $e) {
                logError("Saving to Database", $e->getMessage());
                echo json_encode(["status" => "error", "message" => "Error saving to database."]);
                exit();
            }

            // Generate and output the PDF directly to the browser
            try {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $title . '.pdf"');
                $pdf = new PDF("L", "mm", "A4");
                $pdf->AddPage();
                $pdf->SetXY(1, 11);
                $pdf->SetFont("Arial", "B", 28);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(0, 10, $title, 0, 1, "C");
                $pdf->Ln(10);
                $pdf->SetXY(0, 10);
                $pdf->SetFont("Arial", "B", 28);
                $pdf->SetTextColor(255, 255, 15);
                $pdf->Cell(0, 10, $title, 0, 1, "C");
                $pdf->Ln(10);
                $pdf->SetXY(0, 0);
                $x = 107;
                $y = 35;
                $width = 165;
                $height = 140;
                $textColor = [0, 0, 0];
                $pdf->SetTextArea($x, $y, $width, $height);
                $pdf->MultiCellInArea($width, $height, $content, 18, [0, 0, 0]);

                $pdf->Output("D", $title . ".pdf");
                exit();
            } catch (Exception $e) {
                logError("Generating PDF", $e->getMessage());
                echo json_encode(["status" => "error", "message" => "Error generating PDF."]);
                exit();
            }
        } else {
            logError("OpenAI API", $api->getError());
            echo json_encode(["status" => "error", "message" => "Story generation failed."]);
            exit();
        }
    } else {
        logError("Input Validation", "Invalid input data.");
        echo json_encode(["status" => "error", "message" => "Invalid input data."]);
        exit();
    }
} else {
    logError("Request Method", "Unsupported request method.");
    echo json_encode(["status" => "error", "message" => "Unsupported request method."]);
    exit();
}
?>
>>>>>>> 7f1fbc89e8f546c63e4bb12fd04e61aa2661fc1c
