<style>
.hoz.card{
    display: flex;
    flex-direction: row;
    }
.hoz .card-section{
    flex: auto;
}
</style>

<div class="hoz card event">

    <% if $Image %>
    <% if $Link %><a href="$Link" title="$Title" <% if $Link.OpenInNew %> target="_blank" rel="noopener noreferrer"
        <% end_if %>>
        <% end_if %>
        <% if $Top.Lazy %>
            <picture>
                <source media="(min-width:1024px)" data-srcset="<% if function_exists('FocusFill') %>$Image.FocusFill(240,240).URL<% else %>$Image.Fill(240,240).URL<% end_if %>">
                <source media="(max-width:1023px)" data-srcset="<% if function_exists('FocusFill') %>$Image.FocusFill(240,240).URL<% else %>$Image.Fill(240,240).URL<% end_if %>">
                <img class="swiper-lazy" data-src="<% if function_exists('FocusFill') %>$Image.FocusFill(240,240).URL<% else %>$Image.ScaleMaxWidth(240).URL<% end_if %>" alt="$Image.Title.ATT" width="240" style="width:100%;height:100%;object-fit:cover;">
            </picture>
            <div class="swiper-lazy-preloader"></div>
            <% else %>
            <picture>
                <source media="(min-width:1024px)" srcset="<% if function_exists('FocusFill') %>$Image.FocusFill(240,240).URL<% else %>$Image.Fill(240,240).URL<% end_if %>">
                <source media="(max-width:1023px)" srcset="<% if function_exists('FocusFill') %>$Image.FocusFill(240,240).URL<% else %>$Image.Fill(240,240).URL<% end_if %>">
                <img src="<% if function_exists('FocusFill') %>$Image.FocusFill(240,240).URL<% else %>$Image.ScaleMaxWidth(240).URL<% end_if %>" alt="$Image.Title.ATT" width="240" style="width:100%;height:100%;object-fit:cover;">
            </picture>
        <% end_if %>
        <% if $Link %>
    </a>
    <% end_if %>
    <% end_if %>
    <div class="card-section">
        <span style="margin-bottom: 1rem;" class="label">Multi-Day Event</span>
        <h2 class="card-title">$Title</h2>
       <% if $StartDate || $EndDate %>
            <p class="promo-dates">
                <% if $StartDate %>$StartDate.Nice<% end_if %>
                <% if $StartDate && $EndDate %> &ndash; <% end_if %>
                <% if $EndDate %>$EndDate.Nice<% end_if %>
            </p>
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
        <a class="button tiny hollow" href="$Link">
            <div class="hover"></div>
            <span class="text">Event Details</span>
        </a>
    </div>
</div>