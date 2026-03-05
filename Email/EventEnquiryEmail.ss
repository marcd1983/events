<!doctype html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Event enquiry</title>
  </head>
  <body style="margin:0;padding:0;background:#f6f7f8;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f6f7f8;">
      <tr>
        <td align="center" style="padding:24px 12px;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640"
            style="width:640px;max-width:640px;background:#ffffff;border:1px solid #e6e8eb;border-radius:10px;overflow:hidden;">
            
            <tr>
              <td style="padding:20px 24px;background:#111827;color:#ffffff;">
                <div style="font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.4;">
                  <div style="font-size:18px;font-weight:700;">New Event Enquiry</div>
                  <% if $SubmittedAt %>
                    <div style="font-size:12px;opacity:.9;margin-top:4px;">Submitted: $SubmittedAt</div>
                  <% end_if %>
                </div>
              </td>
            </tr>

            <tr>
              <td style="padding:20px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#111827;">
                  
                  <% if $EventTitle %>
                    <p style="margin:0 0 14px 0;">
                      <strong>Event:</strong> $EventTitle
                      <% if $EventLink %>
                        <br />
                        <strong>Event link:</strong>
                        <a href="$EventLink" style="color:#2563eb;text-decoration:underline;">$EventLink</a>
                      <% end_if %>
                    </p>
                  <% end_if %>

                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                    style="border-collapse:collapse;border:1px solid #e6e8eb;border-radius:8px;overflow:hidden;">
                    <tr>
                      <td style="padding:12px 14px;border-bottom:1px solid #e6e8eb;background:#f9fafb;">
                        <strong>Contact details</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:12px 14px;">
                        <div><strong>Name:</strong> $Name</div>
                        <div><strong>Email:</strong> <a href="mailto:$Email" style="color:#2563eb;text-decoration:underline;">$Email</a></div>
                        <% if $Phone %><div><strong>Phone:</strong> $Phone</div><% end_if %>
                        <% if $PageUrl %><div><strong>Submitted from:</strong> <a href="$PageUrl" style="color:#2563eb;text-decoration:underline;">$PageUrl</a></div><% end_if %>
                      </td>
                    </tr>
                  </table>

                  <div style="height:16px;"></div>

                  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
                    style="border-collapse:collapse;border:1px solid #e6e8eb;border-radius:8px;overflow:hidden;">
                    <tr>
                      <td style="padding:12px 14px;border-bottom:1px solid #e6e8eb;background:#f9fafb;">
                        <strong>Message</strong>
                      </td>
                    </tr>
                    <tr>
                      <td style="padding:12px 14px;">
                        <% if $Message %>
                          <div style="white-space:pre-line;">$Message</div>
                        <% else %>
                          <em>No message provided.</em>
                        <% end_if %>
                      </td>
                    </tr>
                  </table>

                  <p style="margin:18px 0 0 0;color:#6b7280;font-size:12px;">
                    Reply directly to this email to respond to $Name (Reply-To is set to the submitter).
                  </p>
                </div>
              </td>
            </tr>

            <tr>
              <td style="padding:14px 24px;background:#f9fafb;border-top:1px solid #e6e8eb;">
                <div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.4;color:#6b7280;">
                  Sent from your website event enquiry form.
                </div>
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
  </body>
</html>