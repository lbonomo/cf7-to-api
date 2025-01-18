# CF7 to API
This project integrates Contact Form 7 (CF7) with an external API. 
It allows you to send form submissions directly to a specified API endpoint.

## Features
- Seamless integration with Contact Form 7
- Customizable API endpoint
- Error handling and logging

## Installation

1. Clone the repository:
    ```sh
    git clone https://github.com/yourusername/cf7-to-api.git
    ```
2. Navigate to the project directory:
    ```sh
    cd cf7-to-api
    ```
3. Install dependencies:
    ```sh
    npm install
    ```

## Usage

1. Configure the API endpoint in the settings file:
    ```json
    {
      "apiEndpoint": "https://example.com/api/submit"
    }
    ```
2. Add the integration to your Contact Form 7 form by including the following shortcode:
    ```sh
    [cf7_to_api]
    ```

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Contact

For any questions or suggestions, please open an issue or contact the project maintainer at your.email@example.com.