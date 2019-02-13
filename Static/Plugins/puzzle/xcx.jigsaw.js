/*
 *project: jigsaw puzzle effects
 *version: 2.0
 *create: 2013-6-2
 *update: 2013-9-12 10:00
 *author: F2E xiechengxiong
 */
(function(win, doc) {
    var list = [];
    var sIndex = 0;
    var startTime = 0;
    var steps = 0;
    var width = 0;
    var _this = this;
    var Jigsaw = function(obj, opts, src) {
        _this = this;
        var pWidth = obj.clientWidth;
        width = parseInt(pWidth/3);
        obj.style.height = pWidth + "px";
        this.obj = obj;
        this.opts = opts;
		this.sourceImg = src;
        this.opts.beforeInit && this.opts.beforeInit();
        doc.addEventListener('click', this.touchHandler, true);
        doc.addEventListener('touchstart', this.touchHandler, true);
    };
    Jigsaw.prototype = {
        touchHandler: function(e) {
            var tar = e.target;
            var flag = true;
            if(!tar) {
                tar = e.touches[0].target;
                if(e.touches.length !== 1) {
                    flag = false;
                }
            }
            if(flag && tar.tagName === 'IMG') {
                var i = parseInt(tar.title);
                var p = sIndex - i;
                if(i !== sIndex && (Math.abs(p) === 3 || (p === 1 && sIndex % 3 !== 0) || (p === -1 && i % 3 !== 0))) {
                    steps++;
                    _this.opts.changeHandler && _this.opts.changeHandler(steps);
                    _this.changePos(i);
                }
            }
        },
        init: function() {
            steps = 0;
            list.length = 0;
            var fm = doc.createDocumentFragment();
            var tl = this.createList();
            for(var i = 0; i < tl.length; i++) {
                var item = doc.createElement('div');
                item.style.cssText = 'width:'+ width +'px;height:'+ width +'px;';
                item.appendChild(tl[i]);
                fm.appendChild(item);
            }
            this.obj.innerHTML = '';
            this.obj.appendChild(fm);
            startTime = new Date().getTime();
            this._timer();
            this.opts.initHandler && this.opts.initHandler();
        },
        isSuccess: function() {
            var flag = true;
            for(var i = 0; i < 9; i++) {
                if(list[i].id != i) {
                    flag = false;
                    break;
                }
            }
            if(flag) {
                this.timer && clearInterval(this.timer);
                this.opts.successHandler && this.opts.successHandler();
            }
        },
        createList: function() {
            var tl = [];
            for(var i = 0; i < 9; i++) {
                var img = doc.createElement('img');
                var p = this.getPos(i);
                img.id = i +'';
                img.src = _this.sourceImg || '1.jpg';
                img.style.cssText = 'width:'+ (width*3) +'px;height:'+ (width*3) +'px;margin-left:'+ (-p[3]) +'px;margin-top:'+ (-p[0]) +'px;clip:rect('+ p[0] +'px,'+ p[1] +'px,'+ p[2] +'px,'+ p[3] +'px);';
                tl.push(img);
            }
            list = this.getRandom(tl);
            return list;
        },
        changePos: function(i) {
            var iImg = list[i];
            var sImg = list[sIndex];
            var id = sImg.id;
            sImg.title = sIndex;
            sImg.id = iImg.id;
            sImg.style.clip = iImg.style.clip;
            sImg.style.marginLeft = iImg.style.marginLeft;
            sImg.style.marginTop = iImg.style.marginTop;
            iImg.title = i;
            iImg.id = id;
            iImg.style.clip = 'rect(0, 0, 0, 0)';
            iImg.style.marginLeft = '0px';
            iImg.style.marginTop = '0px';
            sIndex = i;
            this.isSuccess();
        },
        getPos: function(i) {
            if(i === 8) {
                return [0, 0, 0, 0];
            }
            var p = [];
            p[0] = width * parseInt(i/3);
            p[1] = width * (i%3) + width;
            p[2] = p[0] + width;
            p[3] = p[1] - width;
            return p;
        },
        getRandom: function(arr) {
            var tArr = [];
            var len = arr.length;
            for (var i = 0; i < len; i++) {
                var r = Math.floor(Math.random()*arr.length);
                if(arr[r].id === '8') {
                    sIndex = i;
                }
                arr[r].title = i;
                tArr[i] = arr[r];
                arr.splice(r, 1);
            }
            return tArr;
        },
        _timer: function() {
            this.timer && clearInterval(this.timer);
            var endTime = new Date().getTime();
            var t = endTime - startTime;
            var h = parseInt(t/3600/1000);
            var m = parseInt((t % (3600*1000))/60/1000);
            var s = parseInt(t % (60*1000)/1000);
            var o = [h < 10 ? '0'+ h : h, m < 10 ? '0'+ m : m, s < 10 ? '0'+ s : s];
            this.opts.runHandler && this.opts.runHandler(o);
            var _this = this;
            this.timer = setInterval(function() {
                _this._timer();
            }, 1000);
        }
    };
    win.Jigsaw = Jigsaw;
})(window, document);
