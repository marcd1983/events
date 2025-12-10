<div class="main grid-container $PageWidth" role="main">
	<% include TitleBar %>
		<% if $Content %>
		<div class="grid-x content">
			<div class="cell">
				$Content
			</div>
		</div>
		<% else %>
			$ElementalArea
		<% end_if %>
 
 <div class="grid-x grid-padding-x">
     <div class="cell small-12 small-order-1 medium-3 large-3 medium-order-2">
       
            <div class="calendar-holder callout">
                <form method="get" class="grid-x grid-padding-x grid-padding-y align-middle" id="event-date-filter">
                    <div class="cell small-12 medium-6">
                        <label class="cut">From
                            <input type="text" name="start" id="dateStart" value="$StartParam" placeholder="YYYY-MM-DD" />
                        </label>
                    </div>
                    <div class="cell small-12 medium-6">
                        <label>To
                            <input type="text" name="end" id="dateEnd" value="$EndParam" placeholder="YYYY-MM-DD" />
                        </label>
                    </div>
                    <div class="cell large-12">
                        <input type="text" name="range" id="dateRange" value="<% if $HasRange %>$StartParam to $EndParam<% end_if %>" placeholder="Pick a date range" />
                        <input type="hidden" name="start" id="hiddenStart" value="$StartParam">
                        <input type="hidden" name="end"   id="hiddenEnd"   value="$EndParam">
                    </div>
                    <div class="cell large-12">
                        <div class="button-group">
                            <button class="button" type="submit">Filter</button>  
                            <button class="button secondary" id="clearFilters" type="button">Clear</button>
                        </div>
                    </div> 
                </form>
            </div>
        
    </div>
    <div class="cell small-12 small-order-2 medium-auto medium-order-1">
        <div class="grid-x grid-margin-x grid-margin-y large-up-{$GridColumn} medium-up-{$GridColumn} small-up-1">
            <% if getEvents %>
            <% loop getEvents %>
                <% if isCurrent %>
                    <div id="event-$ID"  class="cell" data-aos="fade-up">
                        <% include EventCard %>  
                    </div>   
                <% end_if %>    
            <% end_loop %>
            <% else %>
            <div class="cell auto">
                <div class="callout p-40">
                    <h3 style="text-align:center;font-size:1.25rem" class="text-center">There are currently no events,<br>but please check back with us later for possible updates.</h3>
                </div>
            </div>
            <% end_if %>
        </div>
    </div>
</div> 
</div>


   
