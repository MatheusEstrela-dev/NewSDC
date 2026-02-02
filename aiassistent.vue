import React, { useState, useEffect, useRef } from 'react';
import { 
  MessageSquare, 
  Send, 
  Plus, 
  MoreVertical, 
  Search, 
  User, 
  Bot, 
  ChevronLeft, 
  LayoutDashboard,
  CloudRain,
  FileText,
  AlertTriangle,
  Settings,
  X,
  Paperclip,
  HelpCircle,
  ArrowRight,
  ShieldCheck
} from 'lucide-react';

const App = () => {
  const [messages, setMessages] = useState([]); // Começa vazio para mostrar o "Hero"
  const [input, setInput] = useState('');
  const [isSidebarOpen, setIsSidebarOpen] = useState(true);
  const [isThinking, setIsThinking] = useState(false);
  const chatEndRef = useRef(null);

  const scrollToBottom = () => {
    chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    if (messages.length > 0) scrollToBottom();
  }, [messages, isThinking]);

  const handleSend = (content = input) => {
    const textToSend = content || input;
    if (!textToSend.trim()) return;

    const userMsg = {
      id: Date.now(),
      role: 'user',
      content: textToSend,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    };

    setMessages(prev => [...prev, userMsg]);
    setInput('');
    setIsThinking(true);

    setTimeout(() => {
      const aiMsg = {
        id: Date.now() + 1,
        role: 'assistant',
        content: `Certo! Analisando sua solicitação sobre "${textToSend}". O sistema SDC está processando os dados geográficos e meteorológicos em tempo real para fornecer a melhor resposta.`,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      };
      setMessages(prev => [...prev, aiMsg]);
      setIsThinking(false);
    }, 1200);
  };

  const suggestions = [
    { title: "Gostaria de consultar um protocolo RAT", icon: <FileText size={18} /> },
    { title: "Preciso ver o alerta meteorológico de hoje", icon: <CloudRain size={18} /> },
    { title: "Como realizar um novo cadastro de abrigo?", icon: <ShieldCheck size={18} /> },
    { title: "Verificar status da viatura operacional", icon: <AlertTriangle size={18} /> },
    { title: "Gerar relatório de assistência técnica", icon: <LayoutDashboard size={18} /> },
    { title: "Consultar base de dados de voluntários", icon: <User size={18} /> },
  ];

  const historyItems = [
    "Análise de enchente Venda Nova",
    "Relatório de danos Januária",
    "Protocolo #9928 - Pendente",
    "Consulta meteorológica Sul",
    "Dúvida sobre formulário RAT",
    "Ajuda humanitária Juiz de Fora"
  ];

  return (
    <div className="flex h-screen bg-[#F8FAFC] dark:bg-[#0b0f1a] text-slate-800 dark:text-slate-200 font-sans overflow-hidden">
      
      {/* SIDEBAR - REFERÊNCIA ESQUERDA */}
      <aside className={`transition-all duration-300 ease-in-out border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] flex flex-col ${isSidebarOpen ? 'w-80' : 'w-0 opacity-0 overflow-hidden'}`}>
        <div className="p-5 flex flex-col gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
            <input 
              type="text" 
              placeholder="Buscar histórico..." 
              className="w-full pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-800/50 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500/50 transition-all"
            />
          </div>
        </div>

        <div className="flex-1 overflow-y-auto px-3 space-y-1">
          {historyItems.map((text, i) => (
            <button key={i} className="w-full text-left p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/50 group flex items-center gap-3 transition-colors">
              <MessageSquare size={16} className="text-slate-400 group-hover:text-blue-500" />
              <span className="text-sm truncate text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white">{text}</span>
            </button>
          ))}
        </div>

        <div className="p-5 border-t border-slate-200 dark:border-slate-800 space-y-3">
          <button className="flex items-center gap-3 w-full p-2 text-slate-500 hover:text-blue-500 text-sm transition-colors">
            <Settings size={18} /> Configurações
          </button>
          <button className="flex items-center gap-3 w-full p-2 text-slate-500 hover:text-blue-500 text-sm transition-colors">
            <HelpCircle size={18} /> Ajuda e Suporte
          </button>
          
          <div className="mt-4 p-4 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-lg shadow-blue-500/20">
            <p className="text-xs font-bold uppercase tracking-wider mb-1">Status do Sistema</p>
            <p className="text-[10px] opacity-80 mb-3 font-medium">Você está operando no módulo avançado da Defesa Civil.</p>
            <button className="w-full py-2 bg-white/20 hover:bg-white/30 backdrop-blur-md rounded-lg text-xs font-bold transition-all">
              Ver Painel Geral
            </button>
          </div>
        </div>
      </aside>

      {/* ÁREA PRINCIPAL */}
      <main className="flex-1 flex flex-col relative bg-white dark:bg-[#0b0f1a]">
        
        {/* HEADER */}
        <header className="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800">
          <button 
            onClick={() => setIsSidebarOpen(!isSidebarOpen)}
            className="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-400 transition-colors"
          >
            <ChevronLeft className={`transition-transform duration-300 ${!isSidebarOpen ? 'rotate-180' : ''}`} />
          </button>
          
          <div className="flex items-center gap-2">
            <span className="text-xs font-bold text-blue-500 bg-blue-500/10 px-2 py-1 rounded-md uppercase tracking-tighter">SDC IA 2.0</span>
          </div>
        </header>

        {/* CONTEÚDO DINÂMICO */}
        <div className="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-800">
          {messages.length === 0 ? (
            /* ESTADO INICIAL (HERO) - BASEADO NA REFERÊNCIA */
            <div className="min-h-full flex flex-col items-center justify-center p-8 max-w-5xl mx-auto text-center">
              {/* MASCOTE ROBÔ (SVG) */}
              <div className="mb-8 relative">
                <div className="absolute inset-0 bg-blue-500/20 blur-3xl rounded-full scale-150 animate-pulse"></div>
                <svg width="120" height="120" viewBox="0 0 100 100" className="relative drop-shadow-2xl">
                  <circle cx="50" cy="50" r="45" fill="#1e293b" />
                  <rect x="25" y="35" width="50" height="35" rx="10" fill="#334155" />
                  <circle cx="40" cy="50" r="4" fill="#60a5fa" className="animate-pulse" />
                  <circle cx="60" cy="50" r="4" fill="#60a5fa" className="animate-pulse" />
                  <path d="M35 75 Q 50 85 65 75" stroke="#475569" strokeWidth="2" fill="none" />
                </svg>
              </div>

              <h1 className="text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white mb-4">
                Vamos começar com <span className="text-blue-500">sua dúvida aqui</span>
              </h1>
              <p className="text-slate-500 dark:text-slate-400 mb-12 max-w-lg text-lg">
                Eu sou o Assistente de Inteligência da Defesa Civil. Como posso agilizar seus processos hoje?
              </p>

              {/* GRADE DE SUGESTÕES */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                {suggestions.map((item, i) => (
                  <button 
                    key={i}
                    onClick={() => handleSend(item.title)}
                    className="flex items-center justify-between p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/20 hover:border-blue-500/50 hover:bg-blue-50 dark:hover:bg-blue-500/5 transition-all group text-left shadow-sm"
                  >
                    <div className="flex items-center gap-4">
                      <div className="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-500 group-hover:text-blue-500 group-hover:bg-white dark:group-hover:bg-slate-700 transition-colors">
                        {item.icon}
                      </div>
                      <span className="text-sm font-semibold text-slate-700 dark:text-slate-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                        {item.title}
                      </span>
                    </div>
                    <ArrowRight size={16} className="text-slate-300 group-hover:text-blue-500 group-hover:translate-x-1 transition-all" />
                  </button>
                ))}
              </div>
            </div>
          ) : (
            /* VISUALIZAÇÃO DE CHAT ATIVO */
            <div className="max-w-3xl mx-auto py-10 px-4 space-y-8">
              {messages.map((msg) => (
                <div key={msg.id} className={`flex gap-4 ${msg.role === 'user' ? 'flex-row-reverse' : ''}`}>
                  <div className={`w-10 h-10 rounded-xl flex items-center justify-center border shadow-sm flex-shrink-0 ${
                    msg.role === 'user' 
                      ? 'bg-blue-600 border-blue-500 text-white' 
                      : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-blue-500 shadow-md shadow-blue-500/5'
                  }`}>
                    {msg.role === 'user' ? <User size={18} /> : <Bot size={18} />}
                  </div>
                  <div className={`flex flex-col max-w-[85%] ${msg.role === 'user' ? 'items-end' : 'items-start'}`}>
                    <div className={`p-5 rounded-2xl text-sm leading-relaxed shadow-sm ${
                      msg.role === 'user'
                        ? 'bg-blue-600 text-white rounded-tr-none'
                        : 'bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-tl-none'
                    }`}>
                      {msg.content}
                    </div>
                    <span className="text-[10px] text-slate-400 mt-2 font-medium">{msg.timestamp}</span>
                  </div>
                </div>
              ))}
              {isThinking && (
                <div className="flex gap-4">
                  <div className="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-blue-500 shadow-md shadow-blue-500/5">
                    <Bot size={18} />
                  </div>
                  <div className="p-5 bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-2xl rounded-tl-none flex gap-1.5 items-center">
                    <div className="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                    <div className="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                    <div className="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce"></div>
                  </div>
                </div>
              )}
              <div ref={chatEndRef} />
            </div>
          )}
        </div>

        {/* INPUT AREA - REFERÊNCIA DA BASE */}
        <footer className="p-6 bg-gradient-to-t from-white dark:from-[#0b0f1a] via-white dark:via-[#0b0f1a] to-transparent">
          <div className="max-w-4xl mx-auto">
            <div className="relative flex items-center bg-white dark:bg-[#161d2b] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-slate-200/20 dark:shadow-none p-1 focus-within:ring-2 focus-within:ring-blue-500/20 transition-all">
              <button className="p-3 text-slate-400 hover:text-blue-500 transition-colors">
                <Paperclip size={20} />
              </button>
              
              <textarea 
                value={input}
                onChange={(e) => setInput(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    handleSend();
                  }
                }}
                placeholder="Escreva sua mensagem aqui ou escolha uma sugestão..."
                className="flex-1 bg-transparent border-none focus:ring-0 text-sm text-slate-800 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-600 resize-none py-3 px-2 min-h-[48px] max-h-40"
                rows="1"
              />
              
              <button 
                onClick={() => handleSend()}
                disabled={!input.trim()}
                className={`p-3 rounded-xl transition-all m-1 ${
                  input.trim() 
                    ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700' 
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-400'
                }`}
              >
                <Send size={18} />
              </button>
            </div>
            
            <p className="text-[10px] text-center mt-4 text-slate-400 dark:text-slate-600 font-bold uppercase tracking-widest">
              Defesa Civil MG • Assistente Inteligente • 2024
            </p>
          </div>
        </footer>
      </main>
    </div>
  );
};

export default App;