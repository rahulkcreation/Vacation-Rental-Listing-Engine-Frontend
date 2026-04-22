/**
 * view-edit.js — Property Add/Edit Form Controller
 * @package ListingEngineFrontend
 */
(function($){
'use strict';

window.LEF_ViewEdit = {
    mode:'new', propId:0, origStatus:'',
    images:[], selectedAmenities:[], selectedLocation:null, selectedType:null, selectedStatus:'draft',
    blockedDates: new Set(), calBase: new Date(), isMobile: window.innerWidth<992,
    amenitiesList:[], locationsList:[], typesList:[],
    autoSaveTimer:null, saving:false,

    /** Open the form panel */
    open(mode, id){
        this.mode = mode||'new';
        this.resetForm();
        this.propId = id||0;
        $('.lef-host-list-page').hide();
        $('#lef-ve-panel').show();
        $('#lef-ve-mode').val(this.mode);
        $('#lef-ve-nav-heading').text(mode==='edit'?'Back to Listings':'Back to Listings');
        $('#lef-ve-submit-btn').text(mode==='edit'?'UPDATE LISTING':'ADD LISTING');
        this.fetchFormData();
        this.startAutoSave();
    },

    /** Close form, return to listings */
    close(){
        this.stopAutoSave();
        $('#lef-ve-panel').hide();
        $('.lef-host-list-page').show();
        $(document).trigger('lef_sidebar_screen_loaded',['my-listings']);
    },

    /** Reset all form fields */
    resetForm(){
        $('#lef-ve-form')[0].reset();
        this.images=[];this.selectedAmenities=[];this.selectedLocation=null;
        this.selectedType=null;this.selectedStatus='draft';this.blockedDates=new Set();
        this.origStatus='';this.propId=0;
        $('#lef-ve-property-id').val(0);
        $('#lef-ve-image-grid').html('');
        this.renderAmenitiesTags();this.renderDatesSummary();
        $('#lef-ve-location-display').html('<span class="lef-ve-placeholder">Select location...</span>');
        $('#lef-ve-type-display').html('<span class="lef-ve-placeholder">Select type...</span>');
        this.setStatusDisplay('draft');
        this.clearErrors();
    },

    /** Show/hide loader overlay */
    loader(show){
        $('#lef-ve-loader-overlay').toggle(!!show);
    },

    /** Fetch dropdown data + property (if edit) */
    fetchFormData(){
        this.loader(true);
        $.ajax({
            url: lefMyProfileData.ajax_url, type:'POST',
            data:{action:'lef_ve_get_form_data',nonce:lefMyProfileData.nonce,property_id:this.propId},
            success:(r)=>{
                this.loader(false);
                if(!r.success){if(window.LEF_Toast)LEF_Toast.show(r.data.message||'Error','error');return;}
                var d=r.data;
                this.amenitiesList=d.amenities||[];
                this.locationsList=d.locations||[];
                this.typesList=d.types||[];
                this.renderAmenitiesDropdown();
                this.renderLocationDropdown();
                this.renderTypeDropdown();
                if(this.mode==='edit'&&d.property){
                    try {
                        this.populateForm(d.property,d.images,d.block_dates);
                    } catch(err) {
                        console.error('Error populating form:', err);
                        if(window.LEF_Toast)LEF_Toast.show('Error loading property data: ' + err.message, 'error');
                    }
                }
                this.renderCalendar();
            },
            error:()=>{this.loader(false);if(window.LEF_Toast)LEF_Toast.show('Network error','error');}
        });
    },

    /** Populate form fields for edit mode */
    populateForm(p,imgs,dates){
        this.propId=p.id;
        $('#lef-ve-property-id').val(p.id);
        $('#lef-ve-title').val(p.title||'');
        $('#lef-ve-description').val(p.description||'');
        $('#lef-ve-guests').val(p.guests||'');
        $('#lef-ve-bedrooms').val(p.bedroom||p.bedrooms||'');
        $('#lef-ve-beds').val(p.bed||p.beds||'');
        $('#lef-ve-bathrooms').val(p.bathroom||p.bathrooms||'');
        $('#lef-ve-price').val(p.price||'');
        $('#lef-ve-address').val(p.address||'');
        this.origStatus=p.status||'draft';
        // Status: published/rejected -> only allow draft/pending
        var s=(p.status==='published'||p.status==='rejected')?'pending':(p.status||'draft');
        this.selectedStatus=s;this.setStatusDisplay(s);
        // Amenities
        var amRaw = p.amenities||p.amenities_id||'';
        var amIds=[];try{amIds=JSON.parse(amRaw);}catch(e){amIds=String(amRaw).split(',').map(Number).filter(Boolean);}
        this.selectedAmenities=amIds.map(Number);
        this.renderAmenitiesDropdown();this.renderAmenitiesTags();
        // Location
        var locRaw = p.location||p.location_id||null;
        this.selectedLocation=locRaw?Number(locRaw):null;
        if(this.selectedLocation){var loc=this.locationsList.find(l=>l.id==this.selectedLocation);
            if(loc)$('#lef-ve-location-display').html('<span class="lef-ve-single-value">'+this.esc(loc.name)+'</span>');
        }this.renderLocationDropdown();
        // Type
        var typeRaw = p.type||p.type_id||null;
        this.selectedType=typeRaw?Number(typeRaw):null;
        if(this.selectedType){var tp=this.typesList.find(t=>t.id==this.selectedType);
            if(tp)$('#lef-ve-type-display').html('<span class="lef-ve-single-value">'+this.esc(tp.name)+'</span>');
        }this.renderTypeDropdown();
        // Images
        this.images=[];
        if(imgs&&imgs.length){
            imgs.forEach((row, i)=>{
                var arr=[];
                try{
                    arr=JSON.parse(row.image);
                }catch(e){
                    if(row.image && isNaN(row.image)) {
                        arr = [{id: 0, url: row.image, sort_order: i}];
                    }
                }
                if(Array.isArray(arr)){
                    arr.forEach(im=>{this.images.push({id:im.id||0,url:im.url||'',sort_order:im.sort_order||0});});
                }
            });
            this.images.sort((a,b)=>a.sort_order-b.sort_order);
        }
        this.renderImagePreviews();
        // Block dates
        this.blockedDates=new Set();
        if(dates&&dates.length){
            dates.forEach(row=>{
                var arr=[];try{arr=JSON.parse(row.dates);}catch(e){}
                if(Array.isArray(arr))arr.forEach(d=>this.blockedDates.add(d));
            });
        }
        this.renderDatesSummary();
    },

    // ─── AMENITIES MULTI-SELECT ───
    renderAmenitiesDropdown(){
        var dd=$('#lef-ve-amenities-dropdown');dd.html('');
        this.amenitiesList.forEach(a=>{
            var sel=this.selectedAmenities.includes(Number(a.id));
            var div=$('<div>').addClass('lef-ve-option'+(sel?' lef-ve-selected':'')).attr({'role':'option','data-id':a.id});
            div.html('<span class="lef-ve-opt-checkbox">'+(sel?'✓':'')+'</span><span>'+this.esc(a.name)+'</span>');
            div.on('click',(e)=>{e.stopPropagation();this.toggleAmenity(Number(a.id));});
            dd.append(div);
        });
    },
    toggleAmenity(id){
        var idx=this.selectedAmenities.indexOf(id);
        if(idx===-1)this.selectedAmenities.push(id);else this.selectedAmenities.splice(idx,1);
        this.renderAmenitiesDropdown();this.renderAmenitiesTags();
    },
    renderAmenitiesTags(){
        var c=$('#lef-ve-amenities-tags');c.html('');
        if(!this.selectedAmenities.length){c.html('<span class="lef-ve-placeholder">Select amenities...</span>');return;}
        this.selectedAmenities.forEach(id=>{
            var a=this.amenitiesList.find(x=>x.id==id);if(!a)return;
            var tag=$('<span class="lef-ve-tag">'+this.esc(a.name)+' <span class="lef-ve-tag-remove" data-id="'+id+'">&times;</span></span>');
            tag.find('.lef-ve-tag-remove').on('click',(e)=>{e.stopPropagation();this.toggleAmenity(id);});
            c.append(tag);
        });
    },

    // ─── LOCATION SINGLE-SELECT ───
    renderLocationDropdown(){
        var dd=$('#lef-ve-location-dropdown');dd.html('');
        this.locationsList.forEach(l=>{
            var sel=this.selectedLocation===Number(l.id);
            var div=$('<div>').addClass('lef-ve-option'+(sel?' lef-ve-selected':'')).text(l.name).attr('data-id',l.id);
            div.on('click',(e)=>{
                e.stopPropagation();this.selectedLocation=Number(l.id);
                $('#lef-ve-location-display').html('<span class="lef-ve-single-value">'+this.esc(l.name)+'</span>');
                this.closeDropdown('lef-ve-location');this.renderLocationDropdown();
            });
            dd.append(div);
        });
    },

    // ─── TYPE SINGLE-SELECT ───
    renderTypeDropdown(){
        var dd=$('#lef-ve-type-dropdown');dd.html('');
        this.typesList.forEach(t=>{
            var sel=this.selectedType===Number(t.id);
            var div=$('<div>').addClass('lef-ve-option'+(sel?' lef-ve-selected':'')).text(t.name).attr('data-id',t.id);
            div.on('click',(e)=>{
                e.stopPropagation();this.selectedType=Number(t.id);
                $('#lef-ve-type-display').html('<span class="lef-ve-single-value">'+this.esc(t.name)+'</span>');
                this.closeDropdown('lef-ve-type');this.renderTypeDropdown();
            });
            dd.append(div);
        });
    },

    // ─── STATUS SELECT ───
    setStatusDisplay(val){
        this.selectedStatus=val;
        var labels={draft:'Draft',pending:'Pending Review'};
        var dots={draft:'lef-ve-dot-draft',pending:'lef-ve-dot-pending'};
        $('#lef-ve-status-display').html('<span style="display:inline-flex;align-items:center;gap:8px;"><span class="lef-ve-status-dot '+dots[val]+'"></span><span>'+labels[val]+'</span></span>');
        $('#lef-ve-status-dropdown .lef-ve-status-option').removeClass('lef-ve-selected');
        $('#lef-ve-status-dropdown .lef-ve-status-option[data-value="'+val+'"]').addClass('lef-ve-selected');
    },

    // ─── DROPDOWN GENERIC TOGGLE ───
    toggleDropdown(prefix){
        var trigger=$('#'+prefix+'-trigger');
        var dd=$('#'+prefix+'-dropdown');
        var isOpen=dd.hasClass('lef-ve-open');
        this.closeAllDropdowns();
        if(!isOpen){dd.addClass('lef-ve-open');trigger.addClass('lef-ve-open');trigger.attr('aria-expanded','true');}
    },
    closeDropdown(prefix){
        $('#'+prefix+'-dropdown').removeClass('lef-ve-open');
        $('#'+prefix+'-trigger').removeClass('lef-ve-open').attr('aria-expanded','false');
    },
    closeAllDropdowns(){
        $('.lef-ve-dropdown').removeClass('lef-ve-open');
        $('.lef-ve-select-trigger').removeClass('lef-ve-open').attr('aria-expanded','false');
    },

    // ─── IMAGE UPLOAD (Native File Manager) ───
    handleNativeUpload(){
        var self=this;
        if(self.images.length>=10){if(window.LEF_Toast)LEF_Toast.show('Maximum 10 images allowed','error');return;}
        
        // Remove any existing hidden input
        $('#lef-ve-hidden-file-input').remove();
        
        // Create hidden file input
        var $input = $('<input type="file" id="lef-ve-hidden-file-input" multiple accept=".jpg,.jpeg,.png,.webp,.avif" style="display:none;">');
        $input.on('change', function(e) {
            var files = e.target.files;
            if(!files || !files.length) return;
            
            var maxAllowed = 10 - self.images.length;
            if(files.length > maxAllowed){
                if(window.LEF_Toast)LEF_Toast.show('You can only upload ' + maxAllowed + ' more images.','error');
            }
            
            var filesToUpload = Array.from(files).slice(0, maxAllowed);
            
            filesToUpload.forEach(function(file) {
                if(file.size > 1048576){
                    if(window.LEF_Toast)LEF_Toast.show(file.name+' exceeds 1MB limit','error');
                    return;
                }
                
                var ext=(file.name||'').split('.').pop().toLowerCase();
                if(!['jpg','jpeg','png','webp','avif'].includes(ext)){
                    if(window.LEF_Toast)LEF_Toast.show(file.name+': invalid format','error');
                    return;
                }
                
                var tempId = 'temp_' + Date.now() + Math.random().toString(36).substr(2, 5);
                var tempUrl = URL.createObjectURL(file);
                self.images.push({id: tempId, url: tempUrl, sort_order: self.images.length, is_uploading: true});
                self.renderImagePreviews();

                var formData = new FormData();
                formData.append('action', 'lef_ve_upload_property_image');
                formData.append('nonce', lefMyProfileData.nonce);
                formData.append('property_image', file);
                
                $.ajax({
                    url: lefMyProfileData.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", function(evt) {
                            if (evt.lengthComputable) {
                                var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                                $('#progress-' + tempId.replace('.','_')).css('width', percentComplete + '%');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(r) {
                        var idx = self.images.findIndex(function(im){return im.id===tempId;});
                        if(r.success && r.data && r.data.url){
                            if(idx>-1) {
                                self.images[idx].id = r.data.id;
                                self.images[idx].url = r.data.url;
                                delete self.images[idx].is_uploading;
                                self.renderImagePreviews();
                            } else {
                                // User removed the preview before upload finished. Delete the newly uploaded file!
                                $.ajax({
                                    url: lefMyProfileData.ajax_url, type: 'POST',
                                    data: { action: 'lef_ve_delete_property_image', nonce: lefMyProfileData.nonce, image_id: r.data.id, property_id: 0 }
                                });
                            }
                            if(window.LEF_Toast)LEF_Toast.show('Image uploaded successfully','success');
                        } else {
                            if(idx>-1) {
                                self.images.splice(idx, 1);
                                self.renderImagePreviews();
                            }
                            if(window.LEF_Toast)LEF_Toast.show(r.data.message||'Failed to upload image','error');
                        }
                    },
                    error: function() {
                        var idx = self.images.findIndex(function(im){return im.id===tempId;});
                        if(idx>-1) self.images.splice(idx, 1);
                        self.renderImagePreviews();
                        if(window.LEF_Toast)LEF_Toast.show('Network error during upload','error');
                    }
                });
            });
            
            // Cleanup after selection
            $(this).remove();
        });
        
        $('body').append($input);
        $input[0].click(); // use native click
    },
    renderImagePreviews(){
        var grid=$('#lef-ve-image-grid');grid.html('');
        var self = this;
        this.images.forEach((img,i)=>{
            var safeId = String(img.id).replace('.','_');
            var uploadingHtml = img.is_uploading ? '<div class="lef-ve-img-overlay"><div class="lef-ve-progress-bar"><div id="progress-'+safeId+'" class="lef-ve-progress-fill" style="width:0%"></div></div></div>' : '';
            var item=$('<div class="lef-ve-img-item'+(i===0?' lef-ve-cover-img':'')+'" draggable="true" data-idx="'+i+'"><img src="'+this.esc(img.url)+'" alt="Property image '+(i+1)+'">'+uploadingHtml+'<button type="button" class="lef-ve-img-remove" data-idx="'+i+'" aria-label="Remove image">&times;</button></div>');
            item.find('.lef-ve-img-remove').on('click',(e)=>{
                e.preventDefault();
                var idx = $(e.currentTarget).data('idx');
                var imgObj = this.images[idx];
                
                // Remove from UI immediately
                this.images.splice(idx,1);
                this.images.forEach((im,j)=>{im.sort_order=j;});
                this.renderImagePreviews();
                
                // Delete from server and DB immediately
                if(imgObj && imgObj.id && !imgObj.is_uploading && String(imgObj.id).indexOf('temp_')===-1){
                    $.ajax({
                        url: lefMyProfileData.ajax_url, type: 'POST',
                        data: {
                            action: 'lef_ve_delete_property_image',
                            nonce: lefMyProfileData.nonce,
                            image_id: imgObj.id,
                            property_id: this.propId
                        }
                    });
                }
            });
            // Drag and Drop Logic
            item.on('dragstart', function(e) {
                e.originalEvent.dataTransfer.effectAllowed = 'move';
                e.originalEvent.dataTransfer.setData('text/plain', i);
                $(this).addClass('lef-ve-dragging');
            });
            item.on('dragend', function() {
                $(this).removeClass('lef-ve-dragging');
                $('.lef-ve-img-item').removeClass('lef-ve-drag-over');
            });
            item.on('dragover', function(e) {
                e.preventDefault();
                e.originalEvent.dataTransfer.dropEffect = 'move';
                $(this).addClass('lef-ve-drag-over');
            });
            item.on('dragleave', function() {
                $(this).removeClass('lef-ve-drag-over');
            });
            item.on('drop', function(e) {
                e.preventDefault();
                $(this).removeClass('lef-ve-drag-over');
                var draggedIdx = parseInt(e.originalEvent.dataTransfer.getData('text/plain'), 10);
                var targetIdx = parseInt($(this).data('idx'), 10);
                if(draggedIdx !== targetIdx && !isNaN(draggedIdx)){
                    var draggedImg = self.images.splice(draggedIdx, 1)[0];
                    self.images.splice(targetIdx, 0, draggedImg);
                    self.images.forEach((im, j) => { im.sort_order = j; });
                    self.renderImagePreviews();
                }
            });
            grid.append(item);
        });
    },

    // ─── CALENDAR ───
    renderCalendar(){
        var wrap=$('#lef-ve-cal-months');wrap.html('');
        var by=this.calBase.getFullYear(),bm=this.calBase.getMonth();
        var months=['January','February','March','April','May','June','July','August','September','October','November','December'];
        var days=['SUN','MON','TUE','WED','THU','FRI','SAT'];
        if(this.isMobile){
            wrap.html(this.buildMonth(by,bm,true));
            $('#lef-ve-cal-mob-title').text(months[bm]+' '+by);
        }else{
            var ny=bm===11?by+1:by,nm=bm===11?0:bm+1;
            wrap.html(this.buildMonth(by,bm,true)+this.buildMonth(ny,nm,true));
            $('#lef-ve-cal-title').text(months[bm]+' '+by+' — '+months[nm]+' '+ny);
        }
        var self=this;
        wrap.find('.lef-ve-day:not(.lef-ve-day-disabled):not(.lef-ve-day-empty)').on('click',function(){
            var key=$(this).data('date');
            if(self.blockedDates.has(key))self.blockedDates.delete(key);else self.blockedDates.add(key);
            self.renderCalendar();self.renderDatesSummary();
        });
        this.renderDatesSummary();
    },
    buildMonth(y,m,active){
        var months=['January','February','March','April','May','June','July','August','September','October','November','December'];
        var dayNames=['SUN','MON','TUE','WED','THU','FRI','SAT'];
        var fd=new Date(y,m,1).getDay(),dim=new Date(y,m+1,0).getDate();
        var now=new Date();now.setHours(0,0,0,0);
        var h='<div class="lef-ve-cal-month'+(active?' lef-ve-cal-month-active':'')+'">';
        h+='<div class="lef-ve-cal-month-header">'+months[m]+' '+y+'</div>';
        h+='<div class="lef-ve-cal-day-names">';
        dayNames.forEach(d=>{h+='<div class="lef-ve-day-name">'+d+'</div>';});
        h+='</div><div class="lef-ve-cal-days">';
        for(var i=0;i<fd;i++)h+='<div class="lef-ve-day lef-ve-day-empty"></div>';
        for(var d=1;d<=dim;d++){
            var dt=new Date(y,m,d),key=y+'-'+String(m+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
            var past=dt<now,today=dt.getTime()===now.getTime(),sel=this.blockedDates.has(key);
            var cls='lef-ve-day';
            if(today)cls+=' lef-ve-day-today';if(past)cls+=' lef-ve-day-disabled';if(sel)cls+=' lef-ve-day-selected';
            h+='<div class="'+cls+'" data-date="'+key+'">'+d+'</div>';
        }
        h+='</div></div>';return h;
    },
    renderDatesSummary(){
        var sum=$('#lef-ve-dates-summary'),chips=$('#lef-ve-dates-chips'),cnt=$('#lef-ve-dates-count');
        if(!this.blockedDates.size){sum.hide();return;}
        sum.show();cnt.text(this.blockedDates.size+' date(s) blocked');chips.html('');
        var sorted=Array.from(this.blockedDates).sort(),self=this;
        sorted.forEach(key=>{
            var parts=key.split('-'),dt=new Date(+parts[0],+parts[1]-1,+parts[2]);
            var label=dt.toLocaleDateString('en-US',{month:'short',day:'numeric'});
            var chip=$('<span class="lef-ve-date-chip">'+label+' <span class="lef-ve-chip-remove" data-d="'+key+'">&times;</span></span>');
            chip.find('.lef-ve-chip-remove').on('click',function(){self.blockedDates.delete($(this).data('d'));self.renderCalendar();});
            chips.append(chip);
        });
        var clearBtn=$('<button type="button" class="lef-ve-clear-dates-btn">Clear All</button>');
        clearBtn.on('click',function(){self.blockedDates.clear();self.renderCalendar();});
        chips.append(clearBtn);
    },

    // ─── VALIDATION ───
    clearErrors(){$('.lef-ve-error').removeClass('lef-ve-error');},
    markError(sel){$(sel).addClass('lef-ve-error');},
    validate(){
        this.clearErrors();var ok=true;
        if(!$('#lef-ve-title').val().trim()){this.markError('#lef-ve-title');ok=false;}
        if(!$('#lef-ve-description').val().trim()){this.markError('#lef-ve-description');ok=false;}
        if(!parseInt($('#lef-ve-guests').val())){this.markError('#lef-ve-guests');ok=false;}
        if(!parseInt($('#lef-ve-bedrooms').val())&&$('#lef-ve-bedrooms').val()!=='0'){this.markError('#lef-ve-bedrooms');ok=false;}
        if(!parseInt($('#lef-ve-beds').val())&&$('#lef-ve-beds').val()!=='0'){this.markError('#lef-ve-beds');ok=false;}
        if(!parseInt($('#lef-ve-bathrooms').val())&&$('#lef-ve-bathrooms').val()!=='0'){this.markError('#lef-ve-bathrooms');ok=false;}
        if(!parseInt($('#lef-ve-price').val())){this.markError('#lef-ve-price');ok=false;}
        if(!this.selectedAmenities.length){this.markError('#lef-ve-amenities-trigger');ok=false;}
        if(!this.selectedLocation){this.markError('#lef-ve-location-trigger');ok=false;}
        if(!this.selectedType){this.markError('#lef-ve-type-trigger');ok=false;}
        if(!$('#lef-ve-address').val().trim()){this.markError('#lef-ve-address');ok=false;}
        if(this.images.length<5){if(window.LEF_Toast)LEF_Toast.show('Minimum 5 images required','error');ok=false;}
        return ok;
    },

    // ─── SAVE (submit + autosave) ───
    save(isAutoSave){
        if(this.saving)return;
        if(!isAutoSave&&!this.validate())return;
        this.saving=true;
        if(!isAutoSave)this.loader(true);
        var status=this.selectedStatus;
        if(this.mode==='edit'&&this.origStatus==='published')status='pending';
        if(isAutoSave) status='draft';
        var data={
            action:'lef_ve_save_property',nonce:lefMyProfileData.nonce,
            property_id:this.propId,mode:this.mode,
            title:$('#lef-ve-title').val(),description:$('#lef-ve-description').val(),
            guests:$('#lef-ve-guests').val(),bedroom:$('#lef-ve-bedrooms').val(),
            bed:$('#lef-ve-beds').val(),bathroom:$('#lef-ve-bathrooms').val(),
            price:$('#lef-ve-price').val(),address:$('#lef-ve-address').val(),
            amenities:JSON.stringify(this.selectedAmenities),
            location:this.selectedLocation,type:this.selectedType,status:status,
            images:JSON.stringify(this.images),
            block_dates:JSON.stringify(Array.from(this.blockedDates))
        };
        $.ajax({
            url:lefMyProfileData.ajax_url,type:'POST',data:data,
            success:(r)=>{
                this.saving=false;this.loader(false);
                if(r.success){
                    if(r.data.property_id)this.propId=r.data.property_id;
                    $('#lef-ve-property-id').val(this.propId);
                    if(this.mode==='new')this.mode='edit';
                    if(!isAutoSave){
                        if(window.LEF_Toast)LEF_Toast.show(r.data.message||'Saved!','success');
                        this.close();
                    }
                }else{
                    if(!isAutoSave&&window.LEF_Toast)LEF_Toast.show(r.data.message||'Save failed','error');
                }
            },
            error:()=>{this.saving=false;this.loader(false);if(!isAutoSave&&window.LEF_Toast)LEF_Toast.show('Network error','error');}
        });
    },

    startAutoSave(){this.stopAutoSave();this.autoSaveTimer=setInterval(()=>{if($('#lef-ve-title').val().trim())this.save(true);},30000);},
    stopAutoSave(){if(this.autoSaveTimer){clearInterval(this.autoSaveTimer);this.autoSaveTimer=null;}},

    /** HTML escape utility */
    esc(s){if(!s)return '';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');},

    /** Bind all events once */
    bindEvents(){
        var self=this;
        // Back button
        $(document).on('click','#lef-ve-back-btn',function(){self.close();});
        // Upload area
        $(document).on('click','#lef-ve-upload-area',function(){self.handleNativeUpload();});
        $('#lef-ve-upload-area').on('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();self.handleNativeUpload();}});
        // Dropdown triggers
        $(document).on('click','#lef-ve-amenities-trigger',function(e){e.stopPropagation();self.toggleDropdown('lef-ve-amenities');});
        $(document).on('click','#lef-ve-location-trigger',function(e){e.stopPropagation();self.toggleDropdown('lef-ve-location');});
        $(document).on('click','#lef-ve-type-trigger',function(e){e.stopPropagation();self.toggleDropdown('lef-ve-type');});
        $(document).on('click','#lef-ve-status-trigger',function(e){e.stopPropagation();self.toggleDropdown('lef-ve-status');});
        // Status options
        $(document).on('click','.lef-ve-status-option',function(e){e.stopPropagation();self.setStatusDisplay($(this).data('value'));self.closeDropdown('lef-ve-status');});
        // Close dropdowns on outside click
        $(document).on('click',function(e){if(!$(e.target).closest('.lef-ve-custom-select-wrap').length)self.closeAllDropdowns();});
        // Calendar nav
        $(document).on('click','#lef-ve-cal-prev, #lef-ve-cal-mob-prev',function(){self.calBase.setMonth(self.calBase.getMonth()-1);self.renderCalendar();});
        $(document).on('click','#lef-ve-cal-next, #lef-ve-cal-mob-next',function(){self.calBase.setMonth(self.calBase.getMonth()+1);self.renderCalendar();});
        // Integer-only inputs
        $(document).on('keydown','#lef-ve-guests,#lef-ve-bedrooms,#lef-ve-beds,#lef-ve-bathrooms,#lef-ve-price',function(e){if(e.key==='.'||e.key===',')e.preventDefault();});
        $(document).on('input','#lef-ve-guests,#lef-ve-bedrooms,#lef-ve-beds,#lef-ve-bathrooms,#lef-ve-price',function(){this.value=this.value.replace(/[^0-9]/g,'');});
        // Remove error on focus
        $(document).on('focus','.lef-ve-input,.lef-ve-textarea,.lef-ve-number-input',function(){$(this).removeClass('lef-ve-error');});
        // Form submit
        $(document).on('submit','#lef-ve-form',function(e){e.preventDefault();self.save(false);});
        // Responsive calendar
        $(window).on('resize',function(){var was=self.isMobile;self.isMobile=window.innerWidth<992;if(was!==self.isMobile)self.renderCalendar();});
    }
};

$(document).ready(function(){
    LEF_ViewEdit.bindEvents();
});

})(jQuery);
