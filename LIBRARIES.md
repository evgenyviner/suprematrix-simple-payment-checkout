# Third-Party Libraries

This plugin includes the following third-party libraries:

## Stripe PHP SDK

- **Version**: 19.2.0
- **License**: MIT License
- **Source**: https://github.com/stripe/stripe-php
- **Author**: Stripe, Inc.
- **Purpose**: Provides PHP bindings for the Stripe API to process payments securely
- **Bundled Location**: `/vendor/stripe/stripe-php/`
- **Modified**: No - used as-is for security and compatibility
- **Documentation**: https://stripe.com/docs/api

### License Text

The Stripe PHP SDK is licensed under the MIT License:
```
The MIT License (MIT)

Copyright (c) 2010-2024 Stripe, Inc. (https://stripe.com)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
```

### Stripe's Privacy Policy

When using this plugin, payment data is transmitted to Stripe's servers. Please review Stripe's privacy policy: https://stripe.com/privacy

### WordPress Security Compliance

All user input is sanitized before being passed to the Stripe SDK, and all output from the Stripe SDK is escaped before display, in accordance with WordPress security best practices. The Stripe SDK itself is not modified.

## Composer Autoloader

The plugin uses Composer's autoloader (bundled in `/vendor/composer/`):

- **License**: MIT License
- **Source**: https://github.com/composer/composer
- **Purpose**: Automatic PHP class loading