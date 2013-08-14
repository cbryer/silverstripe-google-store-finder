<% include SideBar %>
<div class="content-container unit size3of4 lastUnit">
	<article>
		<h1>$Title</h1>
		<div class="content">$Content</div>
		<div id="store-finder">
			<div id="store-finder-options">
				<input type="text" id="addressInput" size="10"/>
			    <select id="radiusSelect">
			    	<option value="25">25mi</option>
					<option value="50" selected>50mi</option>
					<option value="100">100mi</option>
					<option value="200">200mi</option>
					<option value="300">300mi</option>
			    </select>
				<input type="button" id="searchLocations" value="Search"/>
			</div>
			
			<div><select id="locationSelect" style="width:100%;visibility:hidden"></select></div>
			<div id="map" style="width: 100%; height: 600px;"></div>
		</div>
	</article>
		$Form
		$PageComments
</div>