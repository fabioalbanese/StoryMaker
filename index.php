<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Una Storia per Te / A Story for You</title>
    <link rel="stylesheet" href="style.css"> <!-- Link to the external CSS file for styling -->
</head>
<body>
    <div class="form-container">
        <!-- Header section with instructions -->
        <div class="header">Crea la tua Avventura!<br>Create Your Own Adventure!</div>
        
        <!-- Main form for user input -->
        <form id="storyForm">
            <!-- Language selection field -->
            <div class="form-field">
                <label for="language">Seleziona la lingua della storia:<br>Select the language of the story:</label>
                <select name="language" id="language">
                    <?php
                    // Read languages from localization file
                    $localizationFile = 'localization.txt'; // Path to the localization file
                    if (file_exists($localizationFile)) {
                        $lines = file($localizationFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        foreach ($lines as $line) {
                            list($code, $name) = explode('|', $line);
                            echo "<option value=\"$code\">" . $code . " - " . $name . "</option>";
                        }
                    } else {
                        // Default option if the localization file is not found
                        echo '<option value="en">English</option>';
                    }
                    ?>
                </select>
            </div>
            
            <!-- Include additional form fields from an external file -->
            <?php include 'form-fields.php'; ?>
            
            <!-- Instruction text for users -->
            <div class="instruction-text">
                Clicca il pulsante una sola volta e attendi.<br>Al termine dell'elaborazione,<br>riceverai il file PDF con la storia.<br>La generazione può richiedere<br>alcuni minuti...<br>Non chiudere la pagina!<br>
                <br>
                Click the button once and wait.<br>At the end of processing,<br>you will receive the PDF file with the story.<br>Generation may take<br>a few minutes...<br>Do not close the page!<br>
            </div>
            
            <!-- Submit button for the form -->
            <input type="submit" value="Crea la storia! / Create the story!" class="submit-button" id="submit-button">
        </form>
        
        <!-- Loading spinner displayed while waiting for the response -->
        <div id="loading-spinner" class="loading-spinner">
            <div class="spinner"></div>
        </div>
        
        <!-- Success and error messages -->
        <div id="success-message" class="success-message hidden"></div>
        <div id="error-message" class="error-message hidden"></div>
        
        <!-- Footer note for users -->
        <div class="footer-note">Divertiti a creare la tua avventura! / Have fun creating your adventure!</div>
    </div>
    
    <!-- Attribution section -->
    <div class="footer-attribution">
        Realized by Fabio Albanese
    </div>

    <script>
        // Function to handle form submission asynchronously
        document.getElementById("storyForm").onsubmit = async function(event) {
            event.preventDefault(); // Prevent default form submission

            var age = document.getElementById("age").value; // Get age input
            var name = document.getElementById("name").value; // Get name input
            var language = document.getElementById("language").value; // Get language input

            // Validate form inputs
            if (!name || !age) {
                alert("Per favore, compila tutti i campi. / Please fill in all fields.");
                return false;
            }

            if (isNaN(age) || age < 0 || age > 12) {
                alert("L'età deve essere un numero compreso tra 0 e 12. / Age must be a number between 0 and 12.");
                return false;
            }

            if (name.length > 10 || /[^a-zA-Z]/.test(name)) {
                alert("Il nome non deve contenere spazi o caratteri speciali e deve essere lungo meno di 10 caratteri. / Name should not contain spaces or special characters and must be less than 10 characters.");
                return false;
            }

            // Disable the submit button and show loading spinner
            disableSubmitButton();
            showLoadingSpinner();

            // Create FormData object to send data to the server
            var formData = new FormData();
            formData.append('name', name);
            formData.append('age', age);
            formData.append('language', language);

            try {
                let response = await fetch('create.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    // Check if the response is a PDF
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.includes("application/pdf")) {
                        // Process PDF response
                        const contentDisposition = response.headers.get("Content-Disposition");
                        let fileName = "Generated_Story.pdf"; // Default filename
                        if (contentDisposition && contentDisposition.includes('filename=')) {
                            const fileNameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                            if (fileNameMatch != null && fileNameMatch[1]) {
                                fileName = fileNameMatch[1].replace(/['"]/g, ''); // Remove any quotes from the filename
                            }
                        }

                        // Create a link element to download the PDF
                        const blob = await response.blob();
                        const link = document.createElement('a');
                        link.href = window.URL.createObjectURL(blob);
                        link.download = fileName;
                        link.click();
                    } else {
                        // If not PDF, assume JSON
                        let result = await response.json();
                        if (result.status === 'error') {
                            throw new Error(result.message);
                        }
                    }
                } else {
                    throw new Error('Impossibile creare la storia. / Failed to create the story.');
                }
            } catch (error) {
                document.getElementById('error-message').innerHTML = error.message;
                document.getElementById('error-message').classList.remove('hidden');
            } finally {
                hideLoadingSpinner();
                enableSubmitButton();
            }
        };

        // Function to hide submit button and show spinner
        function disableSubmitButton() {
            var submitButton = document.getElementById("submit-button");
            submitButton.style.display = "none"; // Hide the button
        }

        // Function to show submit button
        function enableSubmitButton() {
            var submitButton = document.getElementById("submit-button");
            submitButton.style.display = "inline-block"; // Show the button
        }

        // Function to show loading spinner
        function showLoadingSpinner() {
            var loadingSpinner = document.getElementById("loading-spinner");
            loadingSpinner.style.display = "block"; // Show the spinner
        }

        // Function to hide loading spinner
        function hideLoadingSpinner() {
            var loadingSpinner = document.getElementById("loading-spinner");
            loadingSpinner.style.display = "none"; // Hide the spinner
        }
    </script>
</body>
</html>



