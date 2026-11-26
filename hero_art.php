<?php
// Рифлёное стекло рисуется одним WebGL-холстом: вся сцена считается на видеокарте за один проход.
// Это убирает мерцание и «деление на части» у рёбер, которое давали десятки отдельных слоёв.
?>
<canvas class="glass"></canvas>
<script>
(function () {
    var cv = document.querySelector('.art .glass');
    if (!cv) return;
    var gl = cv.getContext('webgl', { alpha: true, premultipliedAlpha: true, antialias: false });
    if (!gl) { cv.remove(); return; }

    var VS = 'attribute vec2 p;void main(){gl_Position=vec4(p,0.,1.);}';
    var FS = [
        'precision highp float;',
        'uniform vec2 uRes;uniform float uT,uTi;',
        'uniform vec3 uPa,uPb,uDeep,uG1,uG3;',
        'uniform float uH[12],uC[12],uO[12];',
        'vec2 warp(vec2 p,float t){',
        '  p+=.045*vec2(sin(p.y*5.+t*.35),cos(p.x*4.-t*.30));',
        '  p+=.030*vec2(sin(p.y*9.-t*.22+1.7),cos(p.x*8.+t*.27));return p;}',
        'float blobf(vec2 p,vec2 c,vec2 r,float a){vec2 d=p-c;float ca=cos(a),sa=sin(a);',
        '  d=vec2(ca*d.x+sa*d.y,-sa*d.x+ca*d.y);d/=r;return smoothstep(1.,0.,length(d));}',
        'vec3 scene(vec2 p){float t=uT;p=warp(p,t);',
        '  vec3 c=mix(uPa,uPb,clamp(p.x*.6+p.y*.6,0.,1.));float a;',
        '  a=blobf(p,vec2(.28+.12*sin(t*.11),.55+.10*cos(t*.09)),vec2(.30,.38),t*.07)*.55;c=mix(c,uDeep,a);',
        '  a=blobf(p,vec2(.62+.10*cos(t*.10),.78+.08*sin(t*.12)),vec2(.34,.22),1.-t*.05)*.60;c=mix(c,uG1,a);',
        '  a=blobf(p,vec2(.42+.12*sin(t*.08+2.),.30+.10*cos(t*.11)),vec2(.28,.26),t*.06)*.65;c=mix(c,uG1,a);',
        '  a=blobf(p,vec2(.70+.08*sin(t*.13),.18+.06*cos(t*.10+1.)),vec2(.22,.12),t*.04)*.30;c=mix(c,uG3,a);',
        '  a=blobf(p,vec2(.50+.10*cos(t*.07),.50+.12*sin(t*.09+.5)),vec2(.12,.16),-t*.08)*.24;c=mix(c,uG3,a);',
        '  a=blobf(p,vec2(.22+.08*sin(t*.12),.86+.06*cos(t*.08)),vec2(.28,.16),t*.05)*.50;c=mix(c,uG1,a);',
        '  return c;}',
        'void main(){',
        '  vec2 uv=gl_FragCoord.xy/uRes;uv.y=1.-uv.y;',
        '  float xr=uv.x*12.;float k=floor(xr);float fx=fract(xr);',
        '  float h=1.,c=.5,o=1.;',
        '  for(int i=0;i<12;i++){if(float(i)==k){h=uH[i];c=uC[i];o=uO[i];}}',
        '  float lc=clamp((uTi-(11.-k)*.08)/1.0,0.,1.);float e=1.-pow(1.-lc,3.);h*=e;o*=e;',
        '  float top=c-h*.5,bot=c+h*.5;',
        '  float endf=smoothstep(top,top+h*.2,uv.y)*smoothstep(bot,bot-h*.2,uv.y);',
        '  float xs=(k+.5)/12.+(fx-.5)/12./1.9;',
        '  vec3 col=scene(vec2(xs,uv.y));',
        '  col=mix(col,vec3(1.),.16*(1.-smoothstep(0.,.22,fx)));',
        '  col=mix(col,uDeep,.16*smoothstep(.6,1.,fx));',
        '  col=mix(col,vec3(1.),.4*(1.-smoothstep(0.,.035,fx))*smoothstep(0.,.5,endf));',
        '  float al=o*endf;',
        '  gl_FragColor=vec4(col*al,al);}'
    ].join('\n');

    function sh(type, src) {
        var s = gl.createShader(type);
        gl.shaderSource(s, src); gl.compileShader(s);
        if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) { throw new Error(gl.getShaderInfoLog(s)); }
        return s;
    }
    var pr;
    try {
        pr = gl.createProgram();
        gl.attachShader(pr, sh(gl.VERTEX_SHADER, VS));
        gl.attachShader(pr, sh(gl.FRAGMENT_SHADER, FS));
        gl.linkProgram(pr);
        if (!gl.getProgramParameter(pr, gl.LINK_STATUS)) { throw new Error('link'); }
    } catch (e) { cv.remove(); return; }
    gl.useProgram(pr);

    var buf = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
    var loc = gl.getAttribLocation(pr, 'p');
    gl.enableVertexAttribArray(loc);
    gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
    gl.enable(gl.BLEND);
    gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);

    var U = function (n) { return gl.getUniformLocation(pr, n); };
    var uRes = U('uRes'), uT = U('uT'), uTi = U('uTi');
    var uCol = { uPa: '--pa', uPb: '--pb', uDeep: '--deep', uG1: '--g1', uG3: '--g3' };

    // Форма стекла: длина, положение и прозрачность каждого из 12 рёбер (слева короче и прозрачнее).
    var H = [.30, .44, .58, .70, .80, .88, .86, .96, 1.02, 1.08, 1.10, 1.10];
    var C = [.51, .47, .455, .48, .525, .55, .53, .49, .47, .49, .50, .50];
    var O = [.14, .22, .32, .44, .58, .72, .84, .93, 1, 1, 1, 1];
    gl.uniform1fv(U('uH[0]'), H); gl.uniform1fv(U('uC[0]'), C); gl.uniform1fv(U('uO[0]'), O);

    function hex(v) {
        v = v.trim().replace('#', '');
        if (v.length === 3) v = v.replace(/./g, '$&$&');
        return [parseInt(v.slice(0, 2), 16) / 255, parseInt(v.slice(2, 4), 16) / 255, parseInt(v.slice(4, 6), 16) / 255];
    }
    function colors() {
        var cs = getComputedStyle(document.documentElement);
        for (var n in uCol) { var c = hex(cs.getPropertyValue(uCol[n])); gl.uniform3f(U(n), c[0], c[1], c[2]); }
    }
    function size() {
        var d = Math.min(window.devicePixelRatio || 1, 1.5);
        var w = Math.max(1, Math.round(cv.clientWidth * d)), h = Math.max(1, Math.round(cv.clientHeight * d));
        if (cv.width !== w || cv.height !== h) { cv.width = w; cv.height = h; }
        gl.viewport(0, 0, w, h);
        gl.uniform2f(uRes, w, h);
    }
    var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    var t0 = performance.now(), n = 0;
    function draw(ts) {
        gl.clearColor(0, 0, 0, 0); gl.clear(gl.COLOR_BUFFER_BIT);
        var s = reduce ? 0 : (ts - t0) / 1000;
        gl.uniform1f(uT, s * 1.6);
        gl.uniform1f(uTi, reduce ? 99 : s);
        gl.drawArrays(gl.TRIANGLES, 0, 3);
    }
    function frame(ts) {
        if (!document.hidden) { if (n++ % 30 === 0) colors(); draw(ts); }
        requestAnimationFrame(frame);
    }
    addEventListener('resize', function () { size(); if (reduce) draw(0); });
    size(); colors();
    if (reduce) { draw(0); } else { requestAnimationFrame(frame); }
})();
</script>