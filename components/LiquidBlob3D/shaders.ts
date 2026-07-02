// Ported from the provided vanilla THREE hero (dsg-hero).
// Keep shader logic identical; only uniform wiring is handled in React/R3F.

export const vertexShader = /* glsl */ `
  uniform float u_time;
  uniform float u_intensity;
  uniform vec2  u_pointer;
  varying float vNoise;
  varying float vRim;
  float n3(vec3 p){
    float n1 = sin(p.x*1.7 + u_time*0.8) + sin(p.y*1.3 - u_time*0.6) + sin(p.z*1.9 + u_time*0.4);
    float n2 = sin(p.x*3.4 - u_time*1.6) + sin(p.y*2.6 + u_time*1.2) + sin(p.z*2.5 - u_time*0.9);
    return n1*0.25 + n2*0.12;
  }
  void main(){
    float base = n3(normalize(position)*2.0);
    vec3 dir = normalize(vec3(u_pointer.xy, 0.0) + 0.0001);
    float viewInfluence = dot(normal, dir) * 0.18;
    float disp = (base + viewInfluence) * u_intensity;
    vNoise = disp;
    float swirl = sin(u_time*0.5 + position.y * 2.0) * 0.4;
    vec3 pos = position;
    float cs = cos(swirl);
    float sn = sin(swirl);
    pos.xz = mat2(cs, -sn, sn, cs) * pos.xz;
    vec3 newPos = pos + normal * disp;
    vRim = 1.0 - max(dot(normalize(normal), vec3(0.0, 0.0, 1.0)), 0.0);
    gl_Position = projectionMatrix * modelViewMatrix * vec4(newPos, 1.0);
  }
`;

export const fragmentShader = /* glsl */ `
  precision highp float;
  varying float vNoise;
  varying float vRim;
  uniform float u_time;
  uniform vec3 u_color1;
  uniform vec3 u_color2;
  void main(){
    float tBase = smoothstep(-0.2, 0.45, vNoise);
    float tShift = 0.5 + 0.5 * sin(u_time * 0.5 + vNoise * 3.0);
    float t = mix(tBase, tShift, 0.25);
    vec3 col = mix(u_color1, u_color2, t);
    float rim = pow(clamp(vRim, 0.0, 1.0), 1.5);
    col = mix(col, vec3(1.0), rim * 0.25);
    gl_FragColor = vec4(col, 1.0);
  }
`;

