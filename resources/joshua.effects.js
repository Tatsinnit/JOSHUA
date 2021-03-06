// sparks
var Spark=function(a){var b=this;this.b='http://joshua.einhyrning.com/images/';this.s=['spark.png','spark2.png','spark3.png','spark4.png'];this.i=this.s[this.random(this.s.length)];this.f=this.b+this.i;this.n=document.createElement('img');this.newSpeed().newPoint().display(a).newPoint().fly()};Spark.prototype.display=function(){$(this.n).attr('src',this.f).addClass("spark").css('position','absolute').css('z-index',this.random(9)).css('top',this.pointY).css('left',this.pointX);$(document.body).append(this.n);return this};Spark.prototype.fly=function(){var a=this;$(this.n).animate({"top":this.pointY,"left":this.pointX},this.speed,'linear',function(){a.newSpeed().newPoint().fly()})};Spark.prototype.newSpeed=function(){this.speed=(this.random(10)+5)*3000;return this};Spark.prototype.newPoint=function(){this.pointX=this.random(window.innerWidth-100);this.pointY=this.random(window.innerHeight-100);return this};Spark.prototype.random=function(a){return Math.ceil(Math.random()*a)-1}
// konami code
var konami = readCookie('konami');
if(!konami) {
	var keySequence = [], konamiCode = '38,38,40,40,37,39,37,39,66,65';
	if(window.addEventListener) {
		window.addEventListener('keydown', function(e) {
			keySequence.push(e.keyCode);
			if(keySequence.toString().indexOf(konamiCode) >= 0) {
				eraseCookie('fx'); createCookie('theme', 'contra', expires); createCookie('konami', ' true', expires);
				location.reload();
			}
		}, true);
	}
}
// cylon
function cylon(){
	var cylonize = $(document).width()-$('#cylon').width();
	$('#cylon').animate({
		opacity: 1,
		marginLeft: '+='+cylonize+'px'
	}, 1500, 'swing', function() {
		$('#cylon').animate({
			marginLeft: '0'
		}, 1500, 'swing', function() {
			cylon();
		});
	});
}
// matrix 
function matrix(){
	var matrixOverlayW = window.innerWidth - 20,
		matrixOverlayH = window.innerHeight - 20,
		threadId 	= 1,
		threadCount	= 0,
		threadRemoveId = 1,			
		threadMaxCount = 42,
		threadAddSpeed = 500,
		threadAnimateSpeed = 75,
		threadRemoveSpeed = 5000,
		matrixLetters = 'abcdefghijklmnopqrstuvwxyz$+-*/=%\"\'#&(),.;:?!\\|{}<>[]^~';
	
	function enterMatrix(){
		if(threadCount <= threadMaxCount){
			threadAdd();
			if(threadId == threadMaxCount-1){
				threadRemove();
			}
			setTimeout(enterMatrix, threadAddSpeed);
		}				
	}			
	function threadAdd(){
		var curFontSize = Math.floor((Math.random()*10)+10),
			startChar	= matrixLetters.charAt(Math.floor(Math.random() * matrixLetters.length)),
			opacity = curFontSize/10,
			opacity	= (opacity > 1) ? opacity : opacity * 0.3,
			left= Math.floor((Math.random()*matrixOverlayW)+1),
			threadHeight= Math.floor((Math.random()*matrixOverlayH));

		$('#matrix').append('<div id="thread'+threadId+'" class="thread" style="height:'+threadHeight+'px;left:'+left+'px;font-size:'+curFontSize+'px;z-index:'+curFontSize+';opacity:'+opacity+';">'+startChar+'</div>');
		threadAnimate(threadId, startChar, curFontSize);
		threadId++;
		threadCount++;
	}
	function threadAnimate(threadId, startChar, curFontSize){		
		var nextChar = matrixLetters.charAt(Math.floor(Math.random() * matrixLetters.length));
		$('#thread'+threadId).prepend(nextChar + '<br>');		
		setTimeout(function(){						
				threadAnimate(threadId, startChar, curFontSize);
			}, threadAnimateSpeed);
	}			
	function threadRemove(){
		threadMoveDown(threadRemoveId);
		threadCount--;
		threadRemoveId++;
		setTimeout(threadRemove, threadRemoveSpeed);
	}			
	function threadMoveDown(threadId){
		$('#thread'+threadId).animate({
			'top': matrixOverlayH+50
		}, threadRemoveSpeed, function(){
			$('#thread'+threadId).remove();
			setTimeout(enterMatrix, threadAddSpeed);
		});
	}
	
	$('#matrix').css('width', matrixOverlayW);
	$('#matrix').css('height', matrixOverlayH);
	enterMatrix();
}