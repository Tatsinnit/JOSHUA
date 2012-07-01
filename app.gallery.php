<div id="gallery" class="window">
	<h1>Gallery</h1>
	<div id="slick">
		<div class="slideshow">
<?php // slick gallery
	foreach(scandir("gallery") as $file){
		if(stristr($file, '.jpg') || stristr($file, '.png')){
			$images[] = $file;
		}
	}
	shuffle($images);
	// images
	foreach($images as $image){
		print "\t\t\t".'<div class="slide"><img src="gallery/'.$image.'" width="560" height="345" alt=""></div>'."\n";
	}
?>
		</div>
	</div>
</div>
<script type="text/javascript">
$(function(){
	// slideshow with pager
	$('#slick .slideshow').before('<ul class="thumbs clearfix"/>').cycle({
	    speed:  500,
	    timeout: 5000,
		delay: 5000,
		pause: true,
		pauseOnPagerHover: true,
	    pager: '.thumbs', 
	    pagerAnchorBuilder: function(idx, slide) {
			var item = '<img src="'+$(slide).find('img').attr('src')+'" width="45" height="28">';
			return '<li>'+item+'</li>'; 
	    }
	}, function(){
		
	});
	// slide down pager
	var adjust;
	$('#slick').hover(function(){
		adjust = $(this).find('ul').height()+10;
		$(this).find('ul').animate({
			'top': '-='+adjust+'px'
		});
	}, function(){
		$(this).find('ul').animate({
			'top': '+='+adjust+'px'
		});
	});
});
</script>