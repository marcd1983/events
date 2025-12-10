<div class="cell">
    <% if $Title && $ShowTitle %>
        <% with $HeadingTag %>
            <{$Me} class="element-title">$Up.Title.XML</{$Me}>
        <% end_with %>
    <% end_if %>
    <% if $Content %><div class="element__content">$Content</div><% end_if %>

    <% if $EventList %>
    <% if $Appearance = 'Carousel' %>
        <% include EventCarousel %>
    <% else %>
        <% include EventGrid %>
    <% end_if %>    
    <% end_if %>
</div>