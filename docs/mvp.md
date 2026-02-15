# MVP State

If every line here is true in production, the MVP is "done".

No architecture, no implementation - just observable behaviour.

---

## Public marketing pages

1. A visitor can understand what the service offers from the homepage.
2. A visitor can see available hosting plans and starting prices on the homepage.
3. A visitor can click a plan from the homepage to configure or purchase it.
4. A visitor can view all available plans in a plans grid.
5. A visitor can see key plan attributes (RAM, storage, recommended players) in the grid.
6. A visitor can see when a plan is unavailable.
7. A visitor can choose any available plan from the grid to configure it.
8. A visitor can read a features page describing the platform.
9. A visitor can view the Terms of Service page.
10. A visitor can view the Privacy Policy page.
11. A visitor can access legal links from the site footer.

---

## Plan configuration

12. A visitor can view details for a selected plan.
13. A visitor can select a region for the selected plan.
14. The displayed price updates based on selected region.
15. A visitor can continue to checkout when the selected plan-region is available.
16. A visitor is prevented from purchasing an unavailable plan-region.
17. A visitor can register interest for an unavailable plan-region.
18. The system stores the requested plan and region for each interest registration.

---

## Checkout

19. A visitor must log in or register before completing purchase.
20. A visitor can create an account during checkout.
21. A user can review plan, region, and recurring price before payment.
22. A user must accept the Terms of Service before payment.
23. A user can complete payment through Stripe.
24. After successful payment, the user is returned to the site.
25. If payment fails, the user is clearly informed and can retry checkout.

---

## Post-purchase experience

26. After purchase, the customer lands in the dashboard.
27. A newly purchased server appears in the dashboard within 2 minutes.
28. The server begins provisioning automatically.
29. The server transitions: Provisioning -> Ready automatically.
30. The customer can see when the server becomes ready.
31. The customer can view the connection hostname in the dashboard.
32. The customer can join the server in Minecraft using that hostname.

---

## Pricing & availability

33. Displayed pricing is consistent with the selected region across plan selection and checkout.
34. The customer is charged the same recurring price shown immediately before checkout.
35. The system prevents purchase when capacity is exhausted.
36. When purchase is blocked by capacity, the visitor can express interest instead.
37. Interest registrations always capture and persist the selected plan and region.

---

## Store operations

38. An operator can enable or disable plans.
39. An operator can enable or disable regions.
40. An operator can set base plan pricing.
41. An operator can set region price modifiers.
42. An operator can view interest registrations.
43. An operator can trigger notification emails to interested users.

---

## Connection & identity

44. Every server has a permanent hostname that does not change.
45. The hostname resolves correctly in DNS.
46. If SRV is implemented: the user does not need to type a port.
47. The dashboard displays the connection address clearly and copyable.

---

## Server lifecycle control

48. User can start the server from the dashboard.
49. User can stop the server from the dashboard.
50. User can restart the server from the dashboard.
51. User can reinstall the server choosing Vanilla or Paper.
52. The dashboard shows whether the server is running or stopped.
53. The dashboard shows current online player count.

---

## Panel access

54. User can open the management panel from the dashboard.
55. The user only sees their own server(s) in the panel.
56. Console access works.
57. File upload/download works.

---

## Billing lifecycle

58. User can cancel their subscription.
59. After cancellation period ends, the server is suspended automatically.
60. If payment fails, the server enters a past-due state.
61. After grace period, the server suspends automatically.
62. After extended non-payment, the server is deleted automatically.

---

## Operational correctness

63. A purchased server is created exactly once (no duplicates on retries).
64. DNS records are created automatically for new servers.
65. DNS records are removed when servers are deleted.
66. Server actions (start/stop/reinstall) reliably reach the game server.
67. The system records which node and port each server is assigned to.
68. The platform detects if a server process is offline.
69. Player count updates automatically within ~30 seconds.

---

## Reliability expectations

70. A single failure during provisioning does not create a broken paid server (retry succeeds).
71. Restarting the web app does not lose server state.
72. Restarting the control service does not lose server mappings.
73. Existing servers remain joinable after system restarts.

---

## MVP exit condition

You can onboard real customers and operate the service without manual intervention per order, and every behaviour above works consistently.
