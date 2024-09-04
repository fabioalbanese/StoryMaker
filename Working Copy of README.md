# StoryMaker
A PHP-based story generation app using OpenAI API.


StoryMaker
Overview
StoryMaker is an open-source web application designed to generate customized fantasy adventure stories for children. Users can select various story elements, such as the protagonist's name, age, setting, friends, and enemies. The application then generates a personalized story using OpenAI's GPT-4 model and provides it as a downloadable PDF.

Features
Custom Story Generation: Users can create personalized stories by choosing specific elements like the protagonist's name, age, and setting.
Multi-language Support: The app supports story generation in multiple languages using templates defined in a localization file.
Interactive UI: A user-friendly interface that works on desktops, tablets, and smartphones.
Downloadable PDF: The generated story is provided in PDF format, complete with a decorative background and structured layout.
Project Structure
The project is structured as follows:

index.php: The main front-end of the application. This file contains the HTML and JavaScript for the user interface.
create.php: The back-end script that handles story generation and PDF creation. It communicates with the OpenAI API.
form-fields.php: Contains the HTML form fields for story customization, including the protagonist, setting, friends, and enemies.

style.css: Contains all the CSS styles for the application, ensuring a responsive and attractive design.

localization.txt: A text file that defines story generation templates for different languages. It includes placeholders for dynamic content like names and settings.

images/: A directory containing background images for the PDF and the web page.

errorlog.txt: A log file for recording errors and debugging information.

Getting Started
Prerequisites

To run this application, you will need:

A web server with PHP support (e.g., Apache, Nginx).
An API key from OpenAI to access their GPT-4 model.

Installation
Clone the repository:

git clone https://github.com/your-username/StoryMaker.git

Navigate to the project directory:

cd StoryMaker

Set up the environment:

Make sure your web server is configured to serve the StoryMaker directory.
Ensure PHP has the necessary extensions enabled (e.g., curl, sqlite3).
Insert your OpenAI API Key:

Open create.php in a text editor.
Locate the line where the API key is defined:

define("myAPIKey", "your-api-key-here"); // Insert your OpenAI API Key here
Replace "your-api-key-here" with your actual OpenAI API key.
Run the application:

Open your web browser and navigate to the URL where StoryMaker is hosted (e.g., http://localhost/StoryMaker).
Usage
Select the story language: Choose from the available languages in the dropdown menu.
Fill in the form: Enter the protagonist's name, age, and select the story elements like setting, friends, and enemies.
Generate the story: Click the "Create the story!" button to generate your personalized story.
Download the PDF: Once generated, the story will be available as a PDF download.
Localization
The localization.txt file allows you to define story templates in different languages. Each line in the file has the following format:

mathematica
Copia codice
language_code|Language Name|Story Template
Example:
yaml
Copia codice
en|English|Generate a fantastic adventure story in English, approximately 2000 words...
it|Italian|Genera una storia di avventura fantastica in italiano, di circa 2000 parole...
Customizing the Templates
You can add or modify templates by editing the localization.txt file. Use placeholders like $name, $age, $setting, $friend, and $enemy to dynamically insert user inputs into the story.

Error Logging
The application logs errors to errorlog.txt. This file is helpful for debugging and understanding any issues that may arise during execution. Ensure this file has appropriate write permissions.

Using FPDF
This project uses the FPDF library for generating PDFs. FPDF is a free PHP class for generating PDF files. It allows you to customize the PDF in various ways, including adding images, text, headers, and footers.

License Information for FPDF
FPDF is released under a permissive license that allows modification and redistribution, provided the copyright notice is retained. Please refer to the FPDF website for more information about its license: FPDF License.

How to Customize PDF Generation
If you want to customize how the PDFs are generated, you can modify the PDF class in create.php. This class extends the FPDF class and provides additional methods for setting the header, footer, and text area for the story content.

License
This project is licensed under the Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License. See the LICENSE file for details.

You are free to:
Share — Copy and redistribute the material in any medium or format.
Adapt — Remix, transform, and build upon the material.
Under the following terms:
Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made.
NonCommercial — You may not use the material for commercial purposes.
ShareAlike — If you remix, transform, or build upon the material, you must distribute your contributions under the same license as the original.
Acknowledgments
OpenAI for their powerful GPT-4 model.
FPDF for the PDF generation library.
All contributors and developers who help improve this project.
Contributing
Contributions are welcome! Please fork this repository and submit pull requests for any improvements, bug fixes, or new features.