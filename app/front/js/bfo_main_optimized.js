var f=[],oddsType=1,l=!1,m=[],n={},p=[],q=0,r=null;chartCC=function(){$("#chart-area").empty()};
chartSC=function(a,b,c){$("#chart-window").removeClass("is-visible");$("#chart-window").addClass("no-transition");getComputedStyle($("#chart-window")[0]).display;$("#chart-window")[0].offsetHeight;$("#chart-header").find("div").html(a);yorigin="top";xorigin="left";"1px"!=$("#chart-window").css("min-width")&&(setxcord=b+8,setycord=c+8,b+$("#chart-window").width()>=$(window).width()&&(setxcord=b-$("#chart-window").width(),xorigin="right"),c+$("#chart-window").height()>=$(window).height()&&(setycord=
c-$("#chart-window").height(),yorigin="bottom"),$("#chart-window").css({left:setxcord,top:setycord,"transform-origin":yorigin+" "+xorigin}));getComputedStyle($("#chart-window")[0]).display;$("#chart-window")[0].offsetHeight;$("#chart-window").removeClass("no-transition");$("#chart-window").addClass("is-visible")};
alertSW=function(a,b,c){$("#alert-window").removeClass("is-visible");$("#alert-window").addClass("no-transition");getComputedStyle($("#alert-window")[0]).display;$("#alert-window")[0].offsetHeight;$("#alert-result").removeClass("success error");$("#alert-form").find("input").removeClass("success error");$("#alert-odds").val(a.c);$(".alert-result").removeClass("success error");$(".alert-result").text("");$("#alert-form").find("[name=tn]").val(a.b[1]);$("#alert-form").find("[name=m]").val(a.b[0]);$("#alert-header").find("div").html('Add alert:<span style="font-weight: normal;"> '+
a.f+"</span>");yorigin="top";xorigin="right";null!=$.cookie("bfo_alertmail")&&$("#alert-mail").val($.cookie("bfo_alertmail"));"1px"!=$("#alert-window").css("min-width")&&(setxcord=b+8,setycord=c+8,setxcord=b-$("#alert-window").width(),xorigin="right",c+$("#alert-window").height()>=$(window).height()&&(setycord=c-$("#alert-window").height(),yorigin="bottom"),$("#alert-window").css({left:setxcord,top:setycord,"transform-origin":yorigin+" "+xorigin}));getComputedStyle($("#alert-window")[0]).display;
$("#alert-window")[0].offsetHeight;$("#alert-window").removeClass("no-transition");$("#alert-window").addClass("is-visible")};lO=function(a,b){$.get("/ajax/ajax.LinkOut.php",{operator:a,event:b})};
addToParlay=function(a){if(null!=a){if(25<=m.length)return!1;tmpArr=[];tmpArr.ml=$(a).find("span").first().text();tmpArr.name=$(a).closest("tr").find("th").text();tmpArr.ref=$(a).find("span").first().attr("id").substring(3);found=!1;for(a=0;a<m.length;a++)m[a].ref==tmpArr.ref&&(found=!0);found||m.push(tmpArr)}else if(0==m.length)return!1;tmpText="";pvalue=1;for(a=0;a<m.length;a++){dispLine="";if(null!=f[m[a].ref]){switch(oddsType){case 1:dispLine=f[m[a].ref];break;case 2:dispLine=parseFloat(singleMLToDecimal(f[m[a].ref])).toFixed(2);
break;case 3:dispLine=singleDecimalToAmount(singleMLToDecimal(f[m[a].ref]))}pvalue*=singleMLToDecimal(f[m[a].ref])}else{switch(oddsType){case 1:dispLine=document.getElementById("oID"+m[a].ref).innerHTML;break;case 2:dispLine=parseFloat(singleMLToDecimal(document.getElementById("oID"+m[a].ref).innerHTML)).toFixed(2);break;case 3:dispLine=singleDecimalToAmount(singleMLToDecimal(document.getElementById("oID"+m[a].ref).innerHTML))}pvalue*=singleMLToDecimal(document.getElementById("oID"+m[a].ref).innerHTML)}tmpText+=
'<span>\u00bb</span> <span style="font-weight: 500">'+m[a].name+"</span> "+dispLine+"<br />"}dispValue="";switch(oddsType){case 1:1==m.length?dispValue=m[0].ml:dispValue=oneDecToML(pvalue);break;case 2:dispValue=Math.round(100*pvalue)/100;break;case 3:dispValue=singleDecimalToAmount(pvalue)}$("#parlay-area").html(tmpText);$("#parlay-header").html("Parlay: "+dispValue);return!1};
oddsToMoneyline=function(){1!=oddsType&&(0<f.length&&$('[id^="oID"]').each(function(){this.innerHTML=f[this.id.substring(3)]}),oddsType=1)};oddsToDecimal=function(){2!=oddsType&&(1!=oddsType&&oddsToMoneyline(),1==oddsType&&0==f.length&&$('[id^="oID"]').each(function(){f[this.id.substring(3)]=this.innerHTML}),$('[id^="oID"]').each(function(){this.innerHTML=parseFloat(singleMLToDecimal(this.innerHTML)).toFixed(2)}),oddsType=2)};
oddsToAmount=function(a){var b;null==a?b=$("#format-amount-box1").val():b=a;isNaN(b)||0>b||(oddsToDecimal(),$('[id^="oID"]').each(function(){this.innerHTML="$"+Math.round(b*parseFloat(this.innerHTML)-b)}),oddsType=3,$.cookie("bfo_odds_type",3,{a:999,path:"/"}),$.cookie("bfo_risk_amount",b,{a:999,path:"/"}),$("#format-toggle-text").find("span").first().next().html("Return on $"+b+"  &#9660;"),$("#format-amount-box1").val(b),l&&addToParlay(null))};
singleMLToDecimal=function(a){return"-"==String(a).substring(0,1)?(oddsFloat=parseFloat(String(a).substring(1,String(a).length)),oddsFloat=Math.round(1E5*(100/oddsFloat+1))/1E5,oddsFloat.toString()):"+"==String(a).substring(0,1)?(oddsFloat=parseFloat(String(a).substring(1,String(a).length)),oddsFloat=Math.round(1E5*(oddsFloat/100+1))/1E5,oddsFloat.toString()):"error"};oneDecToML=function(a){return 2<=a?"+"+Math.round(100*(a-1)):2>a?""+Math.round(-100/(a-1)):"error"};
singleDecimalToAmount=function(a,b){var c;c=null==b?document.getElementById("format-amount-box1").value:b;if(isNaN(c)||0>c)return"";c=new String(Math.round(100*(c*a-c)));c=c.slice(0,c.length-2)+"."+c.slice(-2);return"$"+c};
setOddsType=function(a){switch(a){case 1:oddsToMoneyline();$.cookie("bfo_odds_type",1,{a:999,path:"/"});$("#format-toggle-text").find("span").first().next().html("Moneyline &#9660;");break;case 2:oddsToDecimal();$.cookie("bfo_odds_type",2,{a:999,path:"/"});$("#format-toggle-text").find("span").first().next().html("Decimal &#9660;");break;case 3:$("#format-toggle-text").find("span").first().next().html("Amount &#9660;"+$("#format-amount-box1").html())}l&&addToParlay(null)};
notIn=function(a){var b,c,d,e,h,g="",k=0;for(a=a.replace(/[^A-Za-z0-9\+\/\=]/g,"");k<a.length;)b="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=".indexOf(a.charAt(k++)),c="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=".indexOf(a.charAt(k++)),e="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=".indexOf(a.charAt(k++)),h="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=".indexOf(a.charAt(k++)),b=b<<2|c>>4,c=(15&c)<<4|e>>2,d=(3&
e)<<6|h,g+=String.fromCharCode(b),64!=e&&(g+=String.fromCharCode(c)),64!=h&&(g+=String.fromCharCode(d));a="";for(h=c1=c2=e=0;e<g.length;)h=g.charCodeAt(e),128>h?(a+=String.fromCharCode(h),e++):191<h&&224>h?(c2=g.charCodeAt(e+1),a+=String.fromCharCode((31&h)<<6|63&c2),e+=2):(c2=g.charCodeAt(e+1),c3=g.charCodeAt(e+2),a+=String.fromCharCode((15&h)<<12|(63&c2)<<6|63&c3),e+=3);k=new String;for(g=0;g<a.length;g++)h=a.charAt(g),e="!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~".indexOf(h),
0<=e&&(h="!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~".charAt((e+47)%94)),k+=h;return k};getElementsByClassName=function(a,b,c){c=c||[];(new RegExp("\\b"+a+"\\b","g")).test(b.className)&&c.push(b);for(var d=0;d<b.childNodes.length;d++)getElementsByClassName(a,b.childNodes[d],c);return c};
$(document).ready(function(){initPage();"none"!=$("#auto-refresh-container").css("display")&&(null==$.cookie("bfo_autorefresh")||isNaN($.cookie("bfo_autorefresh"))||0!=$.cookie("bfo_autorefresh")?($("#afSelectorOff").removeClass("list-checked"),$("span",$("#afSelectorOff")).css("display","none"),toggleRefresh(!0),$("#afSelectorOn").addClass("list-checked"),$("span",$("#afSelectorOn")).css("display","inline-block")):($("#afSelectorOn").removeClass("list-checked"),$("span",$("#afSelectorOn")).css("display",
"none"),toggleRefresh(!1),$("#afSelectorOff").addClass("list-checked"),$("span",$("#afSelectorOff")).css("display","inline-block")));$("#formatSelector1").click(function(){$('[id^="formatSelector"]').each(function(){$(this).removeClass("list-checked");$("span",this).css("display","none")});setOddsType(1);$(this).addClass("list-checked");$("span",this).css("display","inline-block")});$("#formatSelector2").click(function(){$('[id^="formatSelector"]').each(function(){$(this).removeClass("list-checked");
$("span",this).css("display","none")});setOddsType(2);$(this).addClass("list-checked");$("span",this).css("display","inline-block")});$("#formatSelector3").click(function(){$('[id^="formatSelector"]').each(function(){$(this).removeClass("list-checked");$("span",this).css("display","none")});oddsToAmount();$(this).addClass("list-checked");$("span",this).css("display","inline-block")});$("#format-amount-box1").change(function(){$('[id^="formatSelector"]').each(function(){$(this).removeClass("list-checked");
$("span",this).css("display","none")});oddsToAmount();$("#formatSelector3").addClass("list-checked");$("span",$("#formatSelector3")).css("display","inline-block")});$("#format-amount-box1").keyup(function(a){13==a.keyCode&&$("#format-amount-box1").change()});$("#afSelectorOn").click(function(){$("#afSelectorOff").removeClass("list-checked");$("span",$("#afSelectorOff")).css("display","none");toggleRefresh(!0);$("#afSelectorOn").addClass("list-checked");$("span",$("#afSelectorOn")).css("display","inline-block")});
$("#afSelectorOff").click(function(){$("#afSelectorOn").removeClass("list-checked");$("span",$("#afSelectorOn")).css("display","none");toggleRefresh(!1);$("#afSelectorOff").addClass("list-checked");$("span",$("#afSelectorOff")).css("display","inline-block")});$("#parlay-mode-box").click(function(){m=[];"undefined"==typeof $(this).data("toggled")&&$(this).data("toggled",!1);$(this).data("toggled",!$(this).data("toggled"));$(this).data("toggled")?($("#parlay-mode-box").find(".bfo-check-box").addClass("checked"),
l=!0,$("#parlay-window").addClass("is-visible"),$(document).on("mousemove",function(a){$("#parlay-window").css({left:a.clientX+8,top:a.clientY+8})})):($("#parlay-mode-box").find(".bfo-check-box").removeClass("checked"),l=!1,$(document).off("mousemove"),$("#parlay-window").removeClass("is-visible"),$("#parlay-area").html("Click on a line to add it to your parlay"),$("#parlay-header").html("Parlay"))});$("#search-box1").on("mousedown",function(){$(this).css("color","#fff");$(this).focus();$(this).off("mousedown")});
$("#alert-form").submit(function(a){a.preventDefault();var b={};$("#alert-form :input,select").each(function(){b[this.name]=$(this).val()});$("#alert-submit")[0].disabled=!0;$(".alert-result").removeClass("success error");$(a.target).find("input").removeClass("success error");$(".alert-loader").css("display","inline-block");$.get("api?f=aa",{alertFight:b.m,alertFighter:b.tn,alertBookie:b["alert-bookie"],alertMail:b["alert-mail"],alertOdds:b["alert-odds"],alertOddsType:oddsType},function(c){$(".alert-loader").css("display",
"none");var d="";switch(c){case "1":d="\u2714 Alert added";$.cookie("bfo_alertmail",b["alert-mail"],{a:999,path:"/"});break;case "2":d="\u2714 Alert already exists";break;case "-1":case "-2":case "-3":d="x Error: Missing values ("+c+")";break;case "-4":d="x Invalid e-mail";$("#alert-mail").addClass("error");break;case "-5":d="x Invalid odds format";$("#alert-odds").addClass("error");break;case "-6":d="x Alert limit reached (50)";break;case "-7":d="x Odds already reached";$("#alert-odds").addClass("error");
break;default:d="x Unknown error"}$(".alert-result").addClass(1<=c?"success":"error");$(".alert-result").text(d);$(a.target).find('input[type="submit"]').prop("disabled",!1)})});$("#alert-form-il :submit").on("click",function(a){a.preventDefault();var b={};$("#alert-form-il :input,select").each(function(){b[this.name]=$(this).val()});curbut=$(this);curbut[0].disabled=!0;curbut.prevAll(".alert-result").removeClass("success error");$("#alert-mail-il").removeClass("success error");curbut.prevAll(".alert-result-il").text("");
curbut.prevAll(".alert-loader").css("display","inline-block");$.get("/api?f=aa",{alertFight:$(this).data("mu"),alertFighter:"1",alertBookie:b["alert-bookie-il"],alertMail:b["alert-mail-il"],alertOdds:"-9999",alertOddsType:oddsType},function(a){curbut.prevAll(".alert-loader").css("display","none");var d="";switch(a){case "1":d="\u2714 Alert added";$.cookie("bfo_alertmail",b["alert-mail-il"],{a:999,path:"/"});break;case "2":d="\u2714 Alert already exists";break;case "-1":case "-2":case "-3":d="x Error: Missing values ("+
a+")";break;case "-4":d="x Invalid e-mail";$("#alert-mail-il").addClass("error");break;case "-5":d="x Invalid odds format";break;case "-6":d="x Alert limit reached (50)";break;case "-7":d="x Odds already reached";break;default:d="x Unknown error"}curbut.prevAll(".alert-result-il").addClass(1<=a?"success":"error");curbut.prevAll(".alert-result-il").text(d);0>=a&&(curbut[0].disabled=!1)})});$(".teamPercChange").on("click",function(a){var b=$.parseJSON($(this).attr("data-li")),c=$(this).closest("tr").next("tr").find("th.oppcell").text(),
c=$("#team-name").text()+' <span style="font-weight: normal;">(vs. '+c+") &#150; Mean odds";chartCC();createMIChart(b[0],b[1]);chartSC(c,a.clientX,a.clientY);return!1})});
initPage=function(){oddsType=1;f=[];if(null!=$.cookie("bfo_odds_type")&&!isNaN($.cookie("bfo_odds_type"))){cOddsType=parseInt($.cookie("bfo_odds_type"));switch(cOddsType){case 1:$("#format-toggle-text").find("span").first().next().html("Moneyline &#9660;");break;case 2:$("#format-toggle-text").find("span").first().next().html("Decimal &#9660;");oddsToDecimal();break;case 3:$("#format-toggle-text").find("span").first().next().html(" $"+$.cookie("bfo_risk_amount")+" &#9660;"),$("#format-amount-box1").find("span").first().next().html($.cookie("bfo_risk_amount")),
oddsToAmount($.cookie("bfo_risk_amount"))}$('[id^="formatSelector"]').each(function(){$(this).removeClass("list-checked");$("span",this).css("display","none")});$("#formatSelector"+cOddsType).addClass("list-checked");$("span",$("#formatSelector"+cOddsType)).css("display","inline-block")}$(".table-scroller").each(function(){p.push([$(this),$("table",$(this)),$(this).prev().prev(".table-inner-shadow-left"),$(this).prev(".table-inner-shadow-right")]);$.each(p,function(a,b){b[2].data("scrollLeftVis",
!1);b[3].data("scrollRightVis",!0)})});$(".prop-cell a").on("click",function(){matchup_id=$(this).attr("data-mu");"undefined"==typeof $(this).data("toggled")&&$(this).data("toggled",!1);$(this).data("toggled",!$(this).data("toggled"));$("[data-mu="+matchup_id+"]").data("toggled",$(this).data("toggled"));$(this).data("toggled")?(-1<navigator.appName.indexOf("Microsoft")&&-1==navigator.appVersion.indexOf("MSIE 10.0")?($(this).closest("tr").next("tr.odd").andSelf("tr.odd").nextUntil("tr.even").css("display",
"block"),$("#mu-"+matchup_id).nextUntil("tr.even").css("display","block")):($(this).closest("tr").next("tr.odd").andSelf("tr.odd").nextUntil("tr.even").css("display","table-row"),$("#mu-"+matchup_id).nextUntil("tr.even").css("display","table-row")),$("[data-mu='"+matchup_id+"']").find(".exp-txt").text("\u25bc"),n[matchup_id]=!0):($(this).closest("tr").next("tr.odd").andSelf("tr.odd").nextUntil("tr.even").css("display","none"),$("#mu-"+matchup_id).nextUntil("tr.even").css("display","none"),$("[data-mu='"+
matchup_id+"']").find(".exp-txt").text("\u25ba"),n[matchup_id]=!1);return!1});$(".table-scroller").bind("mousedown touchstart",function(){r=$(this)});$(".table-scroller").on("scroll",function(){var a=$(this);if(a.scrollLeft()==q||!a.is(r))return!1;q=a.scrollLeft();$.each(p,function(b,c){c[0].is(a)||c[0].scrollLeft(a.scrollLeft());c[0].scrollLeft()>=c[1].width()-c[0].width()-10?(c[3].css("width",0+(c[1].width()-c[0].width()-c[0].scrollLeft())),c[3].data("scrollRightVis",!1)):0==c[3].data("scrollRightVis")&&
(c[3].css("width","5px"),c[3].data("scrollRightVis",!0));10>=c[0].scrollLeft()?(c[2].css("width",0+c[0].scrollLeft()),c[2].data("scrollLeftVis",!1)):0==c[2].data("scrollLeftVis")&&(c[2].css("width","5px"),c[2].data("scrollLeftVis",!0))})});$(function(){$("ul.dropdown li").hover(function(){$(this).addClass("hover");$("ul:first",this).css("visibility","visible")},function(){$(this).removeClass("hover");$("ul:first",this).css("visibility","hidden")});$("ul.dropdown li").click(function(){$(this).addClass("hover");
$("ul:first",this).css("visibility","visible")});$("ul.dropdown li ul li").click(function(){$(this).removeClass("hover");$("ul:first",this).css("visibility","hidden")})});$("#chart-window").on("click",function(a){if($(a.target).is(".cd-popup-close")||$(a.target).is("#chart-window"))a.preventDefault(),$(this).removeClass("is-visible"),$("#chart-area").empty()});$("#alert-window").on("click",function(a){if($(a.target).is(".cd-popup-close")||$(a.target).is("#alert-window"))a.preventDefault(),$(this).removeClass("is-visible")});
$(document).keyup(function(a){"27"==a.which&&($("#chart-window").removeClass("is-visible"),$("#chart-area").empty())});$(document).click(function(a){!$(a.target).closest("#chart-window").length&&$("#chart-window").is(":visible")&&($("#chart-window").removeClass("is-visible"),$("#chart-area").empty());$(a.target).closest("#alert-window").length||$("#alert-window").is(":visible")&&$("#alert-window").removeClass("is-visible")});$(".odds-table").find(".but-sg").on("click",function(a){var b=$.parseJSON($(this).attr("data-li"));
if(l)return addToParlay(this);var c=$(this).parent().parent().find("th").find("a").text()+' <span style="font-weight: normal;"> &#150; <a href="'+$(this).closest("table").find("th").eq($(this).parent().index()).find("a").attr("href")+'" target="_blank">'+$(this).closest("table").find("th").eq($(this).parent().index()).find("a").text()+"</a></span>";chartCC();createMChart(b[0],b[1],b[2]);chartSC(c,a.clientX,a.clientY);return!1});$(".odds-table").find(".but-sgp").on("click",function(a){var b=$.parseJSON($(this).attr("data-li"));
if(l)return addToParlay(this);var c=$(this).parent().parent().find("th").text()+' <span style="font-weight: normal;"> &#150; <a href="'+$(this).closest("table").find("th").eq($(this).parent().index()).find("a").attr("href")+'" target="_blank">'+$(this).closest("table").find("th").eq($(this).parent().index()).find("a").text()+"</a></span>";chartCC();createPChart(b[0],b[2],b[1],b[3],b[4]);chartSC(c,a.clientX,a.clientY);return!1});$(".odds-table").find(".but-si").on("click",function(a){var b=$.parseJSON($(this).attr("data-li"));
if(!l){var c=$(this).parent().parent().find("th").text()+' <span style="font-weight: normal;"> &#150; Mean odds';chartCC();createMIChart(b[1],b[0]);chartSC(c,a.clientX,a.clientY)}return!1});$(".odds-table").find(".but-sip").on("click",function(a){var b=$.parseJSON($(this).attr("data-li"));if(!l){var c=$(this).parent().parent().find("th").text()+' <span style="font-weight: normal;"> &#150; Mean odds';chartCC();createPIChart(b[1],b[0],b[2],b[3]);chartSC(c,a.clientX,a.clientY)}return!1});$(".odds-table").find(".but-al").on("click",
function(a){var b={};b.b=$.parseJSON($(this).attr("data-li"));b.c=$(this).closest("tr").find(".bestbet").first().text();b.f=$(this).closest("tr").find("th").text();if(l)return addToParlay(this);alertSW(b,a.clientX,a.clientY);return!1})};addAlert=function(){};refreshPage=function(){$("#content").load("api?f=rp",function(){initPage();$.each(n,function(a,b){1==b&&$('a[data-mu="'+a+'"]').first().trigger("click")})})};updateLine=function(){};
toggleRefresh=function(a){1==a?(refreshId=setInterval(function(){refreshPage()},6E4),$("#autoRefresh").addClass("refresh-ind-spin"),$.cookie("bfo_autorefresh",1,{a:999,path:"/"})):($("#autoRefresh").removeClass("refresh-ind-spin"),$.cookie("bfo_autorefresh",0,{a:999,path:"/"}),"undefined"!==typeof refreshId&&clearInterval(refreshId))};
throttle=function(a,b,c){b||(b=250);var d,e;return function(){var h=c||this,g=+new Date,k=arguments;d&&g<d+b?(clearTimeout(e),e=setTimeout(function(){d=g;a.apply(h,k)},b)):(d=g,a.apply(h,k))}};debounce=function(a,b){var c=null;return function(){var d=this,e=arguments;clearTimeout(c);c=setTimeout(function(){a.apply(d,e)},b)}};stTwitter=function(){window.open("http://twitter.com","twitterwindow","height=450, width=550, top="+($(window).height()/2-225)+", left="+$(window).width()/2+", toolbar=0, location=0, menubar=0, directories=0, scrollbars=0")};
$(function(){FastClick.attach(document.body)});
fightSelected=function(){fightID=$("#webFight")[0].options[$("#webFight")[0].selectedIndex].value;ftitle=$("#webFight")[0].options[$("#webFight")[0].selectedIndex].text.trim();0!=fightID?(type=imageLink="","opening"==$('[name="webLineType"]:checked').val()&&(type+="_o"),"2"==$('[name="webLineFormat"]:checked').val()&&(type+="_d"),0<fightID?imageLink="fights/"+fightID+type+".png":0>fightID&&(imageLink="events/"+Math.abs(fightID)+type+".png"),$('[name="webTestImage"]')[0].src="/img/loading.gif",$("#webHTML").val('\x3c!-- Begin BestFightOdds code --\x3e\n<a href="https://www.bestfightodds.com" target="_blank"><img src="https://www.bestfightodds.com/'+
imageLink+'" alt="'+ftitle+' odds - BestFightOdds" style="width: 216px; border: 0;" /></a>\n\x3c!-- End BestFightOdds code --\x3e'),$("#webForum").val("[url=https://www.bestfightodds.com][img]https://www.bestfightodds.com/"+imageLink+"[/img][/url]"),$('[name="webTestImage"]')[0].src=""+imageLink,$("#webImageLink").val("https://www.bestfightodds.com/"+imageLink),$("#webFields").css({display:""})):$("#webFields").css({display:"none"})};
