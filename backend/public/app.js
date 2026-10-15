/* Auth guard: dashboard exige sessão autenticada no backend. */
(async function enforceAuthentication(){
  try {
    const response = await fetch('api.php?action=me', {credentials:'same-origin',headers:{'Accept':'application/json'}});
    if (!response.ok) { window.location.replace('login.html'); return; }
    const data = await response.json();
    const name = data && data.user && data.user.name;
    if (name) {
      const profile = document.querySelector('.profile-copy strong');
      if (profile) profile.textContent = name;
      const initials = name.split(/\s+/).slice(0,2).map(x=>x[0]).join('').toUpperCase();
      document.querySelectorAll('.avatar').forEach(el=>{el.textContent=initials;});
    }
    window.SENTINELA_CSRF = data.csrf || '';
  } catch (_) {
    window.location.replace('login.html');
  }
})();
const demoConversations = [
  {id:1,name:"Mariana Costa",initials:"MC",app:"Mensagens (demo)",time:"09:18",preview:"Combinado, até mais tarde!",messages:[["received","Oi! Você conseguiu ver o recado da escola?","09:14"],["sent","Sim, obrigado por avisar.","09:16"],["received","Combinado, até mais tarde!","09:18"]]},
  {id:2,name:"Grupo da família",initials:"GF",app:"Mensagens (demo)",time:"08:52",preview:"Bom dia, pessoal 👋",messages:[["received","Bom dia, pessoal 👋","08:45"],["sent","Bom dia! Tudo certo por aqui.","08:52"]]},
  {id:3,name:"Rafael Lima",initials:"RL",app:"Mensagens (demo)",time:"Ontem",preview:"Te envio o material depois.",messages:[["sent","Você tem o material da atividade?","Ontem"],["received","Tenho sim. Te envio o material depois.","Ontem"]]},
  {id:4,name:"Ana Souza",initials:"AS",app:"Mensagens (demo)",time:"Ontem",preview:"Obrigado pela ajuda!",messages:[["received","Consegui resolver, obrigado pela ajuda!","Ontem"],["sent","Que bom! Se precisar, me avise.","Ontem"]]}
];
const activities=[
  {icon:"▤",tone:"icon-violet",title:"Nova mensagem (demonstração)",detail:"Mariana Costa · Mensagens de exemplo",time:"09:18"},
  {icon:"⌖",tone:"icon-orange",title:"Registro de localização fictício",detail:"Casa · dado simulado, sem GPS",time:"08:42"},
  {icon:"◷",tone:"icon-green",title:"Chamada de exemplo",detail:"Rafael Lima · 4 min 32 s",time:"08:17"},
  {icon:"▧",tone:"icon-blue",title:"Resumo de aplicativo simulado",detail:"Navegador · 18 minutos",time:"07:56"}
];
const calls=[
  ["Mariana Costa","Recebida","Hoje, 09:14","2 min 18 s"],
  ["Rafael Lima","Efetuada","Hoje, 08:17","4 min 32 s"],
  ["Número de exemplo","Perdida","Ontem, 18:42","—"],
  ["Ana Souza","Recebida","Ontem, 16:05","1 min 09 s"]
];
const apps=[
  ["W","Mensagens (demo)","1 h 12 min",76],["◎","Navegador de exemplo","48 min",51],["▶","Vídeos (demo)","36 min",38],["♫","Música (demo)","22 min",24],["▦","Calendário (demo)","12 min",13]
];
const web=[
  ["portal-escola.exemplo","Hoje, 08:12","Educação"],
  ["biblioteca.exemplo","Hoje, 07:58","Educação"],
  ["noticias.exemplo","Ontem, 19:34","Notícias"],
  ["pesquisa.exemplo","Ontem, 18:20","Pesquisa"]
];
const $=s=>document.querySelector(s);
const $$=s=>Array.from(document.querySelectorAll(s));
function showToast(message){const toast=$("#toast");toast.textContent=message;toast.classList.add("show");clearTimeout(showToast.timer);showToast.timer=setTimeout(()=>toast.classList.remove("show"),2800)}
function navigate(view){$$(".view").forEach(el=>el.classList.toggle("active-view",el.id==="view-"+view));$$(".nav-item").forEach(el=>el.classList.toggle("active",el.dataset.view===view));const active=$('.nav-item[data-view="'+view+'"]');const title=active?active.textContent.trim():view;$("#pageTitle").textContent=title;$("#heading").textContent=title;$("#sidebar").classList.remove("open");window.scrollTo({top:0,behavior:"smooth"})}
function renderActivity(){$("#activityList").innerHTML=activities.map(a=>'<div class="activity-item"><span class="activity-icon '+a.tone+'">'+a.icon+'</span><div class="activity-copy"><strong>'+a.title+'</strong><small>'+a.detail+'</small></div><span class="activity-time">'+a.time+'</span></div>').join("")}
function renderConversations(filter=""){const list=$("#conversationList");const filtered=demoConversations.filter(c=>(c.name+" "+c.preview).toLowerCase().includes(filter.toLowerCase()));if(!filtered.length){list.innerHTML='<div class="empty-conversation"><h3>Nenhuma conversa encontrada</h3><p>Tente outro termo de pesquisa.</p></div>';return}list.innerHTML=filtered.map((c,i)=>'<button class="conversation-row '+(i===0?"selected":"")+'" data-conversation="'+c.id+'"><span class="contact-avatar">'+c.initials+'</span><span class="contact-copy"><strong>'+c.name+'</strong><small>'+c.preview+'</small></span><span class="conversation-meta">'+c.time+'</span></button>').join("");$$("[data-conversation]").forEach(b=>b.addEventListener("click",()=>{ $$(".conversation-row").forEach(x=>x.classList.remove("selected"));b.classList.add("selected");renderConversation(Number(b.dataset.conversation))}));if(filtered[0])renderConversation(filtered[0].id);else $("#conversationDetail").innerHTML='<div class="empty-conversation"><h3>Sem resultados</h3></div>'}
function renderConversation(id){const c=demoConversations.find(x=>x.id===id);if(!c)return;$("#conversationDetail").innerHTML='<div class="conversation-top"><span class="contact-avatar">'+c.initials+'</span><span><strong>'+c.name+'</strong><small>'+c.app+' · histórico inventado</small></span></div><div class="chat-demo-label">CONVERSA FICTÍCIA — NÃO É UMA CONTA REAL</div><div class="chat-messages">'+c.messages.map(m=>'<div class="chat-bubble '+(m[0]==="sent"?"sent":"")+'">'+m[1]+'<small>'+m[2]+'</small></div>').join("")+'</div>'}
function renderCalls(){$("#callsTable").innerHTML='<div class="table-row table-head"><span>CONTATO</span><span>TIPO</span><span>DATA / HORA</span><span>DURAÇÃO</span></div>'+calls.map(c=>'<div class="table-row"><span>'+c[0]+'</span><span>'+c[1]+'</span><span>'+c[2]+'</span><span>'+c[3]+'</span></div>').join("")}
function renderApps(){$("#appsList").innerHTML=apps.map(a=>'<div class="app-row"><span class="app-logo">'+a[0]+'</span><span class="app-name"><strong>'+a[1]+'</strong><small>Uso simulado no dispositivo</small></span><span class="usage-bar"><i style="width:'+a[3]+'%"></i></span><span class="usage-time">'+a[2]+'</span></div>').join("")}
function renderWeb(){$("#webTable").innerHTML='<div class="table-row table-head"><span>ENDEREÇO DE EXEMPLO</span><span>DATA / HORA</span><span>CATEGORIA</span><span>ORIGEM</span></div>'+web.map(c=>'<div class="table-row"><span>'+c[0]+'</span><span>'+c[1]+'</span><span>'+c[2]+'</span><span><span class="tag">Fictício</span></span></div>').join("")}
$$(".nav-item").forEach(b=>b.addEventListener("click",()=>navigate(b.dataset.view)));
$$("[data-goto]").forEach(b=>b.addEventListener("click",()=>navigate(b.dataset.goto)));
$("#mobileMenu").addEventListener("click",()=>$("#sidebar").classList.toggle("open"));
$("#refreshButton").addEventListener("click",()=>{renderActivity();showToast("Painel demonstrativo atualizado. Nenhum dispositivo foi consultado.")});
$("#dismissBanner").addEventListener("click",()=>$(".demo-banner").remove());
$("#helpButton").addEventListener("click",()=>showToast("Este é um protótipo local com dados fictícios."));
$("#addDevice").addEventListener("click",()=>showToast("Cadastro real ainda não implementado. Nenhum dispositivo foi conectado."));
$("#messageSearch").addEventListener("input",e=>renderConversations(e.target.value));
renderActivity();renderConversations();renderCalls();renderApps();renderWeb();

const logoutButton = document.getElementById('logoutButton');
if (logoutButton) logoutButton.addEventListener('click', async () => {
  try {
    const r = await fetch('api.php?action=logout', {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-CSRF-Token':window.SENTINELA_CSRF || ''},body:'{}'});
    if (r.ok) window.location.replace('login.html');
    else showToast('Não foi possível encerrar a sessão. Atualize e tente novamente.');
  } catch (_) { showToast('Erro de conexão ao encerrar a sessão.'); }
});
