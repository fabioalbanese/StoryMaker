# StoryMaker

## Overview

**StoryMaker** is an open-source web application designed to generate customized fantasy adventure stories for children. Users can select various story elements, such as the protagonist's name, age, setting, friends, and enemies. The application then generates a personalized story using OpenAI's GPT-4 model and provides it as a downloadable PDF.

## Features

- **Custom Story Generation**: Users can create personalized stories by choosing specific elements like the protagonist's name, age, and setting.
- **Multi-language Support**: The app supports story generation in multiple languages using templates defined in a localization file.
- **Interactive UI**: A user-friendly interface that works on desktops, tablets, and smartphones.
- **Downloadable PDF**: The generated story is provided in PDF format, complete with a decorative background and structured layout.

## Project Structure

The project is structured as follows:

- `index.php`: The main front-end of the application. This file contains the HTML and JavaScript for the user interface.
- `create.php`: The back-end script that handles story generation and PDF creation. It communicates with the OpenAI API.
- `form-fields.php`: Contains the HTML form fields for story customization, including the protagonist, setting, friends, and enemies.
- `style.css`: Contains all the CSS styles for the application, ensuring a responsive and attractive design.
- `localization.txt`: A text file that defines story generation templates for different languages. It includes placeholders for dynamic content like names and settings.
- `images/`: A directory containing background images for the PDF and the web page.
- `errorlog.txt`: A log file for recording errors and debugging information.

## Getting Started

### Prerequisites

To run this application, you will need:
- A web server with PHP support (e.g., Apache, Nginx).
- An API key from OpenAI to access their GPT-4 model.

### Installation

1. **Clone the repository:**
   
   ```bash
   git clone https://github.com/your-username/StoryMaker.git

2. **Navigate to the project directory:**
   
   ```bash
   cd StoryMaker

3. **Set up the environment:**
   
Make sure your web server is configured to serve the StoryMaker directory.
Ensure PHP has the necessary extensions enabled (e.g., curl, sqlite3).
Insert your OpenAI API Key:

4. **Open create.php in a text editor.**
   
Locate the line where the API key is defined:

define("myAPIKey", "YOUR_API_KEY"); // Insert your OpenAI API Key here

5. **Replace "YOUR_API_KEY" with your actual OpenAI API key.**
   
6. **Run the application:**

Open your web browser and navigate to the URL where StoryMaker is hosted (e.g., http://localhost/StoryMaker).
Usage
Select the story language: Choose from the available languages in the dropdown menu.
Fill in the form: Enter the protagonist's name, age, and select the story elements like setting, friends, and enemies.
Generate the story: Click the "Create the story!" button to generate your personalized story.

7. **Download the PDF**

8. **LIVE DEMO**
https://www.fabioalbanese.it/lunella-crea-un-racconto-per-te/
