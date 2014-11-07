/**
 * klonuje zadanou sablonu
 * retezce #num# nahradi poradovym cislem kopie sablony (i vicecetne) bez ohledu na smazane kopie
 * retezce #list# se budou nahrazovat aktualnim (bere ohled na smazane kopie) poradovym cislem - POZOR - pouzivat jen v textovych castech sablony (pro udrzeni pozice se obaluje do pomocneho tagu span)
 * <ul>
 * <li>sablonu co se ma klonovat oznacit #code-segment-temp (nebo zadat vlastni)</li>
 * <li>prvek do ktereho se maji vkladat klony oznacit #code-segment-root (nebo zadat vlastni)</li>
 * <li>pridani pres a.code-segment-add (nebo zadat vlastni)</li>
 * <li>odebrani pres a.code-segment-remove (nebo zadat vlastni)</li>
 * </ul>
 *
 * @author Pragodata {@link http://pragodata.cz}; Vlahovic
 * @version 1.2
 * 1.2
 *		- pri pridani prvniho radku se do callback uz taky vraci reference na objekt
 *		- odstranen bug pri pridavani prvniho radku
 *		- odstranen bug ktery zpusoboval dvoji spusteni callback, kdyz efekt nemel nastavenou rychlost
 *		- zvlast callback pro pridani a odebrani
 *
 * 1.1
 *		- pomocne tridy se doplni v sablone samy
 *
 * @todo upravit metody, ktere nemaji byt videt jako private
 * @todo otestovat callback pri odebrani
 *
 * @requires jQuery
 * @return {AddRemoveCodeSegment}
 *
 * @param {string} temp_id [optional] vlastni identifikator sablony (id)
 * @param {string} root_id [optional] vlastni identifikator mista pro vlozeni klonu (id)
 * @param {string} add_class [optional] vlastni identifikator elementu pro pridani klonu (class)
 * @param {string} remove_class [optional] vlastni identifikator elementu pro odebrani klonu (class)
 */
function AddRemoveCodeSegment(temp_id,root_id,add_class,remove_class){
	var self=this;
	var temp=$('#' + (temp_id ? temp_id : 'code-segment-temp'));
	var root=$('#' + (root_id ? root_id : 'code-segment-root'));
	var add=(add_class ? add_class : 'code-segment-add');
	var remove=(remove_class ? remove_class : 'code-segment-remove');
	var next_num;
	var count=0;
	var max_segments;
	var callback_add;
	var callback_remove;
	var efect;
	var temp_is_ready=false;

	/* ************************************************************************ */
	/* init methods */
	/* ************************************************************************ */

	/**
	 * metoda, ktera vse zaridi...
	 * @param {integer} _max_segments [optional] maximalni pocet klonu (0=neomezeny)
	 * @param {string} _efect [optional] jak rychle se maji provest efekty (show, hide); kdy je null, nebo false, tak se nastavi "fast"
	 * @param {function} _callback_add anonymni funkce, ktera se provede po pridani klonu
	 * @param {function} _callback_remove anonymni funkce, ktera se provede bezprostredne pred odstranenim klonu
	 * @returns {AddRemoveCodeSegment}
	 */
	this.init=function(_max_segments,_efect,_callback_add, _callback_remove){
		efect=(_efect===null || _efect===false ? 'fast' : _efect);
		callback_add=_callback_add;
		callback_remove=_callback_remove;
		max_segments=parseInt(_max_segments);

		self.initTemp().initAdd().initRemove().initNextNum();
		return self;
	};

	/**
	 * pripravi sablonu pro kopirovani
	 * @returns {AddRemoveCodeSegment}
	 */
	this.initTemp=function(){
		// prida nezbytnosti
		temp.attr('itemid','#num#');
		temp.find('a.'+remove).attr('rel','#num#');
		// vytvori klon
		var clone=temp.clone().removeAttr('id').addClass('code-segment').hide().wrap('<div class="temp" />');
		temp.remove();
		temp=clone.parent('div.temp');
		if(temp.html()){
			temp_is_ready=true;
		}
		return self;
	};

	/**
	 * povesi udalosti pro pridavani
	 * @returns {AddRemoveCodeSegment}
	 */
	this.initAdd=function(){
		$('a.' + add).click(function(event){
			event.preventDefault();
			event.stopPropagation();
			self.add();
		});
		return self;
	};

	/**
	 * povesi udalosti pro odebirani
	 * @param {integer} only_num
	 * @returns {AddRemoveCodeSegment}
	 */
	this.initRemove=function(only_num){
		$('a.' + remove).click(function(event){
			event.preventDefault();
			event.stopPropagation();
			var segment_num=parseInt($(this).attr('rel'));
			// aby se nekupily udalosti na predchozich kopiich
			if(!only_num || only_num===segment_num){
				self.remove(segment_num);
			}
		});
		return self;
	};

	/**
	 * @returns {AddRemoveCodeSegment}
	 */
	this.initNextNum=function(){
		var act_num=0;
		root.children('.code-segment').each(function(){
			act_num=$(this).attr('itemid');
			count++;
		});
		next_num=parseInt(act_num)+1;
		return self;
	};

	/* ************************************************************************ */
	/* other methods */
	/* ************************************************************************ */

	/**
	 * @returns {AddRemoveCodeSegment}
	 */
	this.add=function(){
//		console.log(new Error().stack);
		if(max_segments && max_segments <= count){
			alert('Maximální počet je ' + max_segments + '.');
		}
		else{
			if(temp_is_ready){
				count++;
				root.append(temp.html().replace(/\#num#/g,next_num).replace(/\#list#/g,'<span class="code-segment-list">' + count + '</span>'));
				self.initRemove(next_num).show(next_num);
				next_num++;
			}
		}
		return self;
	};

	/**
	 * prida prvni polozku, pokud jeste neni
	 * @returns {AddRemoveCodeSegment}
	 */
	this.addFirst=function(){
		if(!count){
			self.add();
		}
		return self;
	};

	/**
	 * @param {integer} segment_num
	 * @returns {AddRemoveCodeSegment}
	 */
	this.remove=function(segment_num){
		var segment=this.getSegment(segment_num);
		if(segment){
			var complete_function=function(){
				segment.remove();
				self.repairList();
				if(callback_remove && typeof callback_remove==='function'){
					callback_remove(segment);
				}
			};
			segment.hide(efect,complete_function());
			count--;
		}
		return self;
	};

	/**
	 * @returns {AddRemoveCodeSegment}
	 */
	this.repairList=function(){
		var list=1;
		root.children('.code-segment').each(function(){
			$(this).find('.code-segment-list').html(list);
			list++;
		});
		return self;
	};

	/**
	 * @param {integer} segment_num
	 * @returns {AddRemoveCodeSegment}
	 */
	this.show=function(segment_num){
		var segment=self.getSegment(segment_num);
		if(segment){
			var complete_function=function(){
				segment.css('display','');
				if(callback_add && typeof callback_add==='function'){
					callback_add(segment);
				}
			};
			segment.show(efect,complete_function());
		}
		return self;
	};

	/**
	 * @param {integer} segment_num
	 * @returns {object}
	 */
	this.getSegment=function(segment_num){
		var ret;
		if(segment_num){
			root.children('.code-segment').each(function(){
				if(parseInt($(this).attr('itemid'))===segment_num){
					ret=$(this);
				}
			});
		}
		return ret;
	};

	return self;
}