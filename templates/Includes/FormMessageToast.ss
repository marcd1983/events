<% if $ContactForm.Message %>
    <div class="toast toast--success toast--auto-hide callout p-40 <% if $ContactForm.MessageType = 'good' %>success<% else %>alert<% end_if %>">
        $ContactForm.Message
    </div>
<% end_if %>
<% if $PromoForm.Message %>
    <div class="toast toast--success toast--auto-hide callout p-40 <% if $PromoForm.MessageType = 'good' %>success<% else %>alert<% end_if %>">
        $PromoForm.Message
    </div>
<% end_if %>
<% if $EventForm.Message %>
    <div class="toast toast--success toast--auto-hide callout p-40 <% if $PromoForm.MessageType = 'good' %>success<% else %>alert<% end_if %>">
        $EventForm.Message
    </div>
<% end_if %>