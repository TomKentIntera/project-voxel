# Application Services

The `app/Services` directory is the backend service layer.

- Put orchestration/business logic in `app/Services/*/Services`.
- Put Stripe-specific data access in `app/Services/Stripe/Repositories`.
- Put Stripe-specific utility code in `app/Services/Stripe/Helpers`.

Current Stripe module layout:

- `app/Services/Stripe/Helpers`
- `app/Services/Stripe/Repositories`
- `app/Services/Stripe/Services`
