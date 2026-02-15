# MVP State

If every line here is true in production, the MVP is "done".

No architecture, no implementation - just observable behaviour.

---

## Customer purchase & provisioning

1. A new user can create an account and log in.
2. A logged-in user can select a plan and region (Germany).
3. If capacity is available, the user can complete checkout successfully.
4. After payment, a server appears in the dashboard within 2 minutes.
5. The server transitions: Provisioning -> Ready automatically.
6. The user receives a connection hostname.
7. The user can join the server in Minecraft using that hostname.

---

## Connection & identity

8. Every server has a permanent hostname that does not change.
9. The hostname resolves correctly in DNS.
10. If SRV is implemented: the user does not need to type a port.
11. The dashboard displays the connection address clearly and copyable.

---

## Server lifecycle control

12. User can start the server from the dashboard.
13. User can stop the server from the dashboard.
14. User can restart the server from the dashboard.
15. User can reinstall the server choosing Vanilla or Paper.
16. The dashboard shows whether the server is running or stopped.
17. The dashboard shows current online player count.

---

## Panel access

18. User can open the management panel from the dashboard.
19. The user only sees their own server(s) in the panel.
20. Console access works.
21. File upload/download works.

---

## Billing lifecycle

22. User can cancel their subscription.
23. After cancellation period ends, the server is suspended automatically.
24. If payment fails, the server enters a past-due state.
25. After grace period, the server suspends automatically.
26. After extended non-payment, the server is deleted automatically.

---

## Capacity handling

27. The system prevents purchase when capacity is exhausted.
28. Instead of checkout failure, the user can register interest.
29. The user can choose plan and region when registering interest.
30. The interest request is stored successfully.
31. Operator can trigger notification emails to interested users.

---

## Operational correctness

32. A purchased server is created exactly once (no duplicates on retries).
33. DNS records are created automatically for new servers.
34. DNS records are removed when servers are deleted.
35. Server actions (start/stop/reinstall) reliably reach the game server.
36. The system records which node and port each server is assigned to.
37. The platform detects if a server process is offline.
38. Player count updates automatically within ~30 seconds.

---

## Reliability expectations

39. A single failure during provisioning does not create a broken paid server (retry succeeds).
40. Restarting the web app does not lose server state.
41. Restarting the control service does not lose server mappings.
42. Existing servers remain joinable after system restarts.

---

## MVP exit condition

You can onboard real customers and operate the service without manual intervention per order, and every behaviour above works consistently.
