var ReferrerKiller=(function(){PUB={};function escapeDoubleQuotes(str){return str.split('"').join('\\"');}function escapeSimpleQuotes(str){return str.split("'").join("\\'");}function htmlToNode(html){var container=document.createElement('div');container.innerHTML=html;return container.firstChild;}function objectToHtmlAttributes(obj){var attributes=[],value;for(var name in obj){value=obj[name];attributes.push(name+'="'+escapeDoubleQuotes(value)+'"');}return attributes.join(' ');}function htmlString(html,iframeAttributes,headExtra){var iframeAttributes=iframeAttributes||{},defaultStyles='border:none;overflow:hidden;',id;if('style' in iframeAttributes){iframeAttributes.style=defaultStyles+iframeAttributes.style;}else{iframeAttributes.style=defaultStyles;}id='__referrer_killer_'+(new Date).getTime()+Math.floor(Math.random()*9999);return '<iframe style="border 1px solid #ff0000" scrolling="no" frameborder="no" allowtransparency="true" '+objectToHtmlAttributes(iframeAttributes)+'id="'+id+'" src="javascript:\'<!doctype html><html><head><meta charset=\\\'UTF-8\\\'><style>*{margin:0;padding:0;border:0;}</style>'+(headExtra?encodeURIComponent(escapeSimpleQuotes(headExtra)):'')+'</head><script>function resizeWindow(){var elems=document.getElementsByTagName(\\\'*\\\'),width=0,'+'height=0,first=document.body.firstChild,elem;if(first.offsetHeight&&first.offsetWidth){width=first.offsetWidth;height=first.offsetHeight;}else{for(var i in elems){elem=elems[i];if(!elem.offsetWidth){continue;}width=Math.max(elem.offsetWidth,width);height=Math.max(elem.offsetHeight,height);}}var ifr=parent.document.getElementById(\\\''+id+'\\\');ifr.height=height;ifr.width=width;}</script><body onload=\\\'resizeWindow()\\\'>\'+decodeURIComponent(\''+encodeURIComponent(html)+'\')+\'</body></html>\'"></iframe>';}function linkHtml(url,innerHTML,anchorParams,iframeAttributes,styleInnerIframe,headExtra){var html;innerHTML=innerHTML||false;if(!innerHTML){innerHTML=url;}anchorParams=anchorParams||{};if(!('target' in anchorParams)||'_self'===anchorParams.target){anchorParams.target='_top';}html='<style>'+encodeURIComponent(styleInnerIframe)+'</style><a class="link" rel="noreferrer" href="'+escapeDoubleQuotes(encodeURIComponent(url))+'" '+objectToHtmlAttributes(anchorParams)+'>'+innerHTML+'</a>';return htmlString(html,iframeAttributes,headExtra);}PUB.linkHtml=linkHtml;return PUB;})();