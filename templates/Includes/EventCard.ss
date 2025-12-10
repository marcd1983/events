<div class="card event">
    <% if $Image %>
    <% if $Link %><a href="$Link" title="$Title" <% if $Link.OpenInNew %> target="_blank" rel="noopener noreferrer"
        <% end_if %>>
        <% end_if %>
        <% if $Top.Lazy %>
        <picture>
            <source media="(min-width:1024px)" data-srcset="<% if function_exists('FocusFill') %>$Image.FocusFill(600,600).URL<% else %>$Image.Fill(600,600).URL<% end_if %>">
            <source media="(max-width:1023px)" data-srcset="<% if function_exists('FocusFill') %>$Image.FocusFill(600,600).URL<% else %>$Image.Fill(600,600).URL<% end_if %>">
            <img class="swiper-lazy" data-src="<% if function_exists('FocusFill') %>$Image.FocusFill(600,600).URL<% else %>$Image.ScaleMaxWidth(600).URL<% end_if %>" alt="$Image.Title.ATT" width="600" height="600" style="width:100%;height:auto;">
        </picture>
        <div class="swiper-lazy-preloader"></div>
        <% else %>
        <picture>
            <source media="(min-width:1024px)" srcset="<% if function_exists('FocusFill') %>$Image.FocusFill(600,600).URL<% else %>$Image.Fill(600,600).URL<% end_if %>">
            <source media="(max-width:1023px)" srcset="<% if function_exists('FocusFill') %>$Image.FocusFill(600,600).URL<% else %>$Image.Fill(600,600).URL<% end_if %>">
            <img src="<% if function_exists('FocusFill') %>$Image.FocusFill(600,600).URL<% else %>$Image.ScaleMaxWidth(600).URL<% end_if %>" alt="$Image.Title.ATT" width="600" height="600" style="width:100%;height:auto;">
        </picture>
        <% end_if %>
        <% if $Link %>
    </a>
    <% end_if %>
    <% end_if %>
    <div class="card-section">
        <span style="margin-bottom: 1rem;" class="label">Multi-Day Event</span>
        <h2 class="card-title">$Title</h2>
        <% if $SubTitle %>
        <p class="sub-title">$SubTitle</p>
        <% end_if %>
        <% if StartDate && EndDate %>
        <% end_if %>
        <% if EventTimeDisplay %>
        <p class="dates">$EventTimeDisplay </p>
        <% end_if %>
        <% if $Time %>
        <p class="time">Time: $Time</p>
        <% end_if %>
        <% if $Location %>
        <p class="location">Location: $Location</p>
        <% end_if %>
        <% if $StrippedEventContent %>
        <div class="main-info">$StrippedEventContent</div>
        <% end_if %>
        <a class="button hollow" href="$Link">
            <div class="hover"></div>
            <span class="text">Event Details</span>
        </a>
    </div>
</div>