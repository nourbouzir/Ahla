# Demo script: ten minutes, main journey (issue #40)

## Before you start

```bash
php artisan migrate:fresh --seed        # demo data (never in production)
npm run build && php artisan serve      # http://localhost:8000
php artisan queue:work                  # optional: sends the queued e-mails and translations
```

Every demo account has the password `password`.

| Role | Email | Notes |
|------|-------|-------|
| Admin | `admin@ahla.test` | `AHLA_ADMIN_PASSWORD` in `.env` overrides the password |
| Tourist | `tourist@ahla.test` (Lea, French) | has one confirmed booking with Amel |
| Tourist | `sam@ahla.test` (Sam, English) | has a pending request to Youssef |
| Host (verified) | `amel@ahla.test`, `youssef@ahla.test`, `ines@`, `mehdi@`, `sana@`, `karim@`, `rania@`, `walid@`, `leila@`, `hichem@ahla.test` | ten cities and languages |
| Host (pending) | `omar@ahla.test` | application waiting for the admin |
| Host (rejected) | `nadia@ahla.test` | shows the "apply again" path |

## The walkthrough

1. **Home (1 min).** Open the home page. Switch the language with the buttons in the header: English, Français, العربية. Arabic flips the whole layout right to left. Shrink the window to phone width: the menu collapses.
2. **Find a host (2 min).** Click *Find a host*. Choose city *Tunis*, language *Français*, interest *Food*, press *Search*. Each card shows the **Verified** badge, a match percentage and the **"Why this host"** tags (for example "Speaks Français", "Shares your interest: Food"). Point out Mehdi: new on Ahla, no rating, still listed. Try a city with nobody (Tozeur plus an interest nobody has) to show the empty state.
3. **Book (2 min).** Log in as `tourist@ahla.test`. Open Amel's profile and press *Request booking* on "A market morning". Pick a date, a time between 09:00 and 17:00 and 2 people. The page says the card is only reserved now. Send the request. The booking page shows *Requested* and *Reserved*, and no phone number yet.
4. **Host accepts (2 min).** Log out, log in as `amel@ahla.test`. *Booking requests* shows the request with the time limit. Press *Accept*. The booking becomes *Confirmed*, the payment *Paid*. Try to request a second booking at an overlapping time as another tourist: the page explains that the host is already booked.
5. **Chat (1 min).** Open the booking, *Open the chat*. As Lea write in French, as Amel answer in English: each side reads the other's message in their own language (when the translation service is running) and can press *Show original*. Without the service, the original is shown with "Translation unavailable".
6. **Admin (1 min).** Log in as `admin@ahla.test`. *Admin* lists host applications: open Omar's, download the (encrypted at rest) document, schedule a video call with any https link, approve. Open *Bookings and payments* and the *Audit log* to see who changed what.
7. **Cancellation (1 min).** As Amel (or as Lea) cancel a confirmed booking. A host cancelling gets the guest refunded in full. When the start is less than 24 hours away, the guest's booking page also lists up to three other verified hosts in the same city.

## Things to point out

- Nothing is confirmed until the payment is captured. Declined, expired and cancelled bookings release or refund the money.
- A host's phone number appears only after confirmation, and only if the host agreed (Profile > phone checkbox).
- Failed e-mail or translation never stops a booking.
