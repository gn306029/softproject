class ActionHandler {
    constructor(module, action) {
        this.php = true;
        this.module = this.constructor.name.split("_", 2)[0];
        this.action = this.constructor.name.split("_", 2)[1];
        this.history=false;
        this.goBack=true;
        this.args = new FormData();
        this.contentType;
    }
    run() {
        let self = this;
        window.onpopstate=(event)=>{
            var data=event.state;
            if(data==null){
                self.showModal("回上一頁","此為最上頁。");
            }else{
                this.loadModuleScript(data.module,data.action);
                var actionHandler=eval("new "+data.module+"_"+data.action+"('"+data.position_id+"')");
                var key=Object.keys(data)
                for(var i in data){
                    actionHandler[i]=data[i];
                }
                actionHandler.goBack=false;
                actionHandler.run();
            }
        };
        $(document).ready(function(){
            // $(window).keydown(function(event){
            //     if(event.keyCode == 13) {
            //          event.preventDefault();
            //          return false;
            //     }
            //   });
            self.hasCkeditor();
            if(self.php){
                self.addArg("module", self.module);
                self.addArg("action", self.action);
                if (typeof self.args == "object") {
                    self.contentType = false;
                } else if (typeof self.args == "string") {
                    self.contentType = 'application/x-www-form-urlencoded; charset=UTF-8';
                }
                var t="";
                $.ajax({
                    type: "POST",
                    url: "./module.php ",
                    data: self.args,
                    dataType: "json",
                    processData: false,
                    contentType: self.contentType,
                    beforeSend:function(){
                        t=setTimeout(()=>{
                            $("#allLoading").removeClass("d-none");
                            $("#content").addClass("d-none");
                        },500);
                    },
                    success: function(json) {
                        if(json["status_code"]=="503"){
                            self.loadModuleScript("logout","doLogout");
                            var script=(new logout_doLogout("body"));
                            script.maintain=true;
                            script.run();
                        }else{
                            self.ajaxSuccess(json);
                        }
                    },
                    error: function(json) {
                        self.showModal("錯誤", "伺服器錯誤!");
                    },
                    complete:function(){
                        clearTimeout(t);
                        $("#allLoading").addClass("d-none");
                        $("#content").removeClass("d-none");
                        $(".popover").popover("hide");
                    }
                });
            } else {
                self.showResult();
            }
            //上一頁
            if(self.history && self.goBack){
                if(typeof(self.args)=="object"){
                    var object ="";
                    self.args.forEach(function(value, key){
                        object+=(key+"="+value+"&");

                    });
                    self.args=object;
                }
                var key=Object.keys(self);
                var value=[]
                for(var i in key){
                    value[key[i]]=self[key[i]]
                }
                history.pushState(value,self.module+"_"+self.action);
            }
        });
    }
    addArg(key, value) {
        let self = this;
        if (typeof self.args == "object") {
            self.args.append(key, value);
        } else if (typeof self.args == "string") {
            if (self.args == null) {
                self.args = key + "=" + value;
            } else {
                self.args += "&" + key + "=" + value;
            }
        }
    }
    // 新增已序列的參數
    addSerializeArg(value) {
        let self = this;
        if (typeof self.args == "object") {
            let splitParm = value.split("&");
            for (let i in splitParm) {
                let ParmKey = splitParm[i].split("=")[0];
                let ParmVal = splitParm[i].split("=")[1];
                self.args.append(ParmKey, ParmVal);
            }
        } else if (typeof self.args == "string") {
            if (self.args == null) {
                self.args += value;
            } else {
                self.args += "&" + value;
            }
        }
    }

    loadCSS(id, src) {
        var script = $("#" + id);
        if (script.length == 0) {
            $("head").append("<link rel='stylesheet' type='text/css' id='" + id + "' href='./include/" + src + "' />");
        }
    }
    loadScript(id, src) {
        var script = $("#" + id);
        if (script.length == 0) {
            $("head").append("<script id='" + id + "' src='./include/" + src + "'></script>");
        }
    }
    loadModuleScript(module, action) {
        let src = "./modules/" + module + "/js/" + module + "_" + action + ".js";
        let id = module + "_" + action;
        if ($("#" + id).length == 0) {
            $("head").append(`<script id="` + id + `" src="` + src + `"></script>`);
        }
    }
    // can extend 3 button
    showModal(title, body, buttonName, btnFunction) {
        
        $("#modal .icon-area").remove();
        $("#modal .modal-dialog").removeClass("modal-md-lg"); // Test
        $("#modal .modal-dialog").removeClass("modal-lg");
        $("#modal .modal-title").html(title);
        $("#modal .modal-body").html(body);

        $("#modal .modal-footer button.btn.theme-style").each(function() {
            $(this).addClass("d-none");
            $(this).html("");
            $(this).off("click");
        });

        if (buttonName != undefined && typeof buttonName === "string") {
            $("#modal .modal-footer button.btn.theme-style").eq(2).removeClass("d-none");
            $("#modal .modal-footer button.btn.theme-style").eq(2).html(buttonName);
            $("#modal .modal-footer button.btn.theme-style").eq(2).off("click").on("click", () => {
                $(btnFunction);
            });
        } else if (buttonName != undefined && Array.isArray(buttonName)) {
            if (Array.isArray(btnFunction)) {
                if (btnFunction.length < 3 || buttonName < 3) {
                    if (btnFunction.length === buttonName.length) {
                        let index = 0;
                        $("#modal .modal-footer button.btn.theme-style").each(function() {
                            if (index < buttonName.length) {
                                $(this).removeClass("d-none");
                                $(this).html(buttonName[index]);
                                $(this).off("click").on("click", btnFunction[index]);
                            } else {
                                $(this).addClass("d-none");
                            }
                            index++;
                        });
                    } else {
                        throw new ArrayException("btnFunction num is not match buttonName").message;
                    }
                } else {
                    throw new ArrayException("Array Num is over limit button num").message;
                }
            } else {
                throw new ArrayException("parm is not an array").message;
            }
        } else {
            $("#modal .modal-footer button.btn.theme-style").addClass("d-none");
        }
        $("#modal").modal("show");
    }
    
    showAlert(title, body, buttonName, btnFunction) {
        $("#modal2 .modal-dialog").removeClass("modal-lg");
        $("#modal2 .modal-dialog").removeClass("modal-md-lg"); // Test
        $("#modal2 .icon-area").remove();
        $("#modal2 .modal-footer button.btn.theme-style").each(function() {
            $(this).addClass("d-none");
            $(this).html("");
            $(this).off("click");
        });
        
        $("#modal2 .modal-title").html(title);
        $("#modal2 .modal-body").html(body);
        if (buttonName != undefined && typeof buttonName === "string") {
            $("#modal2 .modal-footer button.btn.theme-style").eq(2).removeClass("d-none");
            $("#modal2 .modal-footer button.btn.theme-style").eq(2).html(buttonName);
            $("#modal2 .modal-footer button.btn.theme-style").eq(2).off("click").on("click", () => {
                $(btnFunction);
            });
        } else if (buttonName != undefined && Array.isArray(buttonName)) {
            if (Array.isArray(btnFunction)) {
                if (btnFunction.length < 3 || buttonName.length < 3) {
                    if (btnFunction.length === buttonName.length) {
                        let index = 0;
                        $("#modal2 .modal-footer button.btn.theme-style").each(function() {
                            if (index < buttonName.length) {
                                $(this).removeClass("d-none");
                                $(this).html(buttonName[index]);
                                $(this).off("click").on("click", btnFunction[index]);
                            } else {
                                $(this).addClass("d-none");
                            }
                            index++;
                        });
                    } else {
                        throw new ArrayException("btnFunction num is not match buttonName").message;
                    }
                } else {
                    throw new ArrayException("Array Num is over limit button num").message;
                }
            } else {
                throw new ArrayException("parm is not an array").message;
            }
        } else {
            $("#modal2 .modal-footer button.btn.theme-style").addClass("d-none");
        }
        $('#modal2').modal('show');
    }
    hasCkeditor() {
        try {
            if ($("#cke_content")) {
                $("#cke_content").remove();
                $("#content").css("visibility", "").css("display", "");
            }
        } catch (e) {
            console.log(e);
        }
    }
    printDoc({content="",useHead=true}){
        let w= window.open("/Khashing/print.html","_blank");
        if(useHead){
            content = `<p style='text-align:center;'><img id="logo" src="/Khashing/include/img/22.jpg" style="width:4.65cm;" /></p>` + content;
        }
        
        w.document.onreadystatechange=function(){
	        if(this.readyState==='complete'){
	            this.onreadystatechange=function(){};
	            w.focus();
	        }
	    };
	    w.addEventListener("load",function(){
	        let imgIndex = 0; // 紀錄目前已載入幾張圖片
	    
    	    let checkImgLoaded = (imgLen) =>{
    	        if(imgLen === imgIndex){
    	            return true;
    	        }else{
    	            return false;
    	        }
    	    }
    	    
	        w.document.body.innerHTML= content;
	        
	        let img = w.document.getElementsByTagName("img");
	        if(img.length!=0){
	            for(let i in img){
                    img[i].addEventListener("load",function(){
                        imgIndex += 1;
                        if(checkImgLoaded(img.length)){
                            w.print();
                            //w.close();
                        };
                    });
                }
	        }else{
	            w.print();
                //w.close();
	        }
            
	    });
    }
    getToday(){
        let today = new Date();
        let dd = today.getDate();
        let mm = today.getMonth()+1; 
        let yyyy = today.getFullYear();
        
        if(dd<10) {
            dd = '0'+dd
        } 
        
        if(mm<10) {
            mm = '0'+mm
        }
        
        return yyyy + "-" + mm + "-" + dd;
    }
}

// 自定義錯誤
function ArrayException(message) {
    this.message = message;
    this.name = 'ArrayException';
}