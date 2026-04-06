# Security Policy

## Supported Versions

| Version | Supported          |
|---------|--------------------|
| 2.x     | Yes                |
| 1.x     | Security fixes only|
| < 1.0   | No                 |

## Reporting a Vulnerability

**Please do not open public GitHub issues for security vulnerabilities.**

To report a security vulnerability, please email [nasrulhazim.m@gmail.com](mailto:nasrulhazim.m@gmail.com) with:

- A description of the vulnerability
- Steps to reproduce the issue
- Any relevant logs or screenshots
- Your suggested fix (if any)

You can expect:

- **Acknowledgment** within 48-72 hours
- **Status update** within 7 days
- **Fix timeline** communicated once the issue is triaged

We follow a responsible disclosure process: fixes are developed and released before public disclosure.

## Security Considerations

### Data Sensitivity

This package captures and transmits application error data including exception messages, stack traces, SQL queries, HTTP request details, and custom content. **This data may contain Personally Identifiable Information (PII).**

As the consumer, you are responsible for:

- Configuring hidden headers and parameters to mask sensitive values
- Ensuring compliance with your organization's data handling policies (GDPR, HIPAA, SOC2, etc.)
- Reviewing what data is captured before deploying to production

### Transport Security

- **HTTP Driver**: Uses HTTPS (`https://nadi.pro/api`) by default. Credentials are sent via HTTP headers, never in URL parameters.
- **OpenTelemetry Driver**: Defaults to `http://localhost:4318` for local development. **Always use HTTPS endpoints in production.**
- **Log Driver**: Writes unencrypted JSON files to disk. Ensure appropriate filesystem permissions are set.

### Credential Management

- Store `NADI_API_KEY` and `NADI_APP_KEY` in environment variables, never in source code
- Rotate API keys regularly
- Credentials are validated at configuration time and never logged

### Core SDK

This package uses [nadi-pro/nadi-php](https://github.com/nadi-pro/nadi-php) as the core SDK. See its [SECURITY.md](https://github.com/nadi-pro/nadi-php/blob/2.0/SECURITY.md) for additional security details.

### Dependencies

Run `composer audit` regularly to check for known vulnerabilities in dependencies.
