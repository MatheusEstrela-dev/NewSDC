import React, { useState, useEffect } from 'react';
import { 
  ShieldAlert, 
  FileText, 
  CheckCircle2, 
  AlertTriangle, 
  Lock, 
  Scale, 
  UserCheck,
  X,
  ChevronDown
} from 'lucide-react';

const App = () => {
  const [isOpen, setIsOpen] = useState(true);
  const [hasScrolledToBottom, setHasScrolledToBottom] = useState(false);
  const [agreed, setAgreed] = useState(false);
  const [showToast, setShowToast] = useState(false);

  const handleScroll = (e) => {
    const { scrollTop, scrollHeight, clientHeight } = e.target;
    if (scrollHeight - scrollTop <= clientHeight + 50) {
      setHasScrolledToBottom(true);
    }
  };

  const handleAccept = () => {
    if (agreed && hasScrolledToBottom) {
      setShowToast(true);
      setTimeout(() => {
        setIsOpen(false);
        setShowToast(false);
      }, 2000);
    }
  };

  const Section = ({ icon: Icon, title, children }) => (
    <div className="mb-8 group">
      <div className="flex items-center gap-3 mb-3 pb-2 border-b border-slate-100">
        <div className="p-2 bg-blue-50 text-blue-700 rounded-lg group-hover:bg-blue-700 group-hover:text-white transition-colors duration-300">
          <Icon size={20} />
        </div>
        <h3 className="text-lg font-bold text-slate-800 uppercase tracking-tight">{title}</h3>
      </div>
      <div className="pl-2 text-slate-600 leading-relaxed text-sm md:text-base">
        {children}
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-slate-200 flex items-center justify-center p-4 font-sans">
      {/* Botão de demonstração para reabrir caso fechado */}
      {!isOpen && (
        <button 
          onClick={() => setIsOpen(true)}
          className="bg-blue-700 hover:bg-blue-800 text-white px-6 py-3 rounded-full shadow-lg transition-all transform hover:scale-105 flex items-center gap-2"
        >
          <FileText size={20} />
          Visualizar Termos do SDC
        </button>
      )}

      {/* Backdrop */}
      {isOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 md:p-10">
          <div 
            className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
            onClick={() => !agreed && setIsOpen(false)}
          />
          
          {/* Modal Container */}
          <div className="relative bg-white w-full max-w-4xl max-h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden animate-in fade-in zoom-in duration-300">
            
            {/* Header */}
            <div className="bg-blue-900 p-6 text-white flex justify-between items-center shrink-0">
              <div className="flex items-center gap-4">
                <div className="bg-white/20 p-2 rounded-lg">
                  <ShieldAlert size={32} />
                </div>
                <div>
                  <h2 className="text-xl md:text-2xl font-bold leading-tight">Sistema de Defesa Civil (SDC)</h2>
                  <p className="text-blue-100 text-xs md:text-sm font-medium">Termo de Uso e Responsabilidade do Portal Geral</p>
                </div>
              </div>
              <button 
                onClick={() => setIsOpen(false)}
                className="p-2 hover:bg-white/10 rounded-full transition-colors"
                title="Fechar"
              >
                <X size={24} />
              </button>
            </div>

            {/* Content Area */}
            <div 
              onScroll={handleScroll}
              className="flex-1 overflow-y-auto p-6 md:p-10 bg-slate-50/50 custom-scrollbar"
            >
              <div className="bg-amber-50 border-l-4 border-amber-400 p-4 mb-8 rounded-r-lg">
                <div className="flex gap-3">
                  <AlertTriangle className="text-amber-600 shrink-0" size={20} />
                  <p className="text-sm text-amber-800">
                    <strong>Atenção:</strong> Leia atentamente todos os itens abaixo. Este sistema é monitorado e de uso estritamente institucional. O acesso requer ciência integral das responsabilidades civis e criminais.
                  </p>
                </div>
              </div>

              <Section icon={FileText} title="1. Natureza do Serviço">
                <p>O Sistema de Defesa Civil (SDC) é uma plataforma oficial do Estado de Minas Gerais, destinada à gestão de riscos, desastres e assistência técnica. O uso deste portal é estritamente institucional e técnico.</p>
              </Section>

              <Section icon={UserCheck} title="2. Responsabilidade Individual">
                <div className="space-y-4">
                  <p><strong>2.1. Identificação:</strong> Todo acesso às áreas restritas do SDC é monitorado. O usuário é legalmente responsável por todas as ações realizadas sob suas credenciais (login/senha ou certificado digital).</p>
                  <p><strong>2.2. Uso Indevido:</strong> Qualquer tentativa de acesso a módulos não autorizados, extração em massa de dados (scraping) ou ataques de negação de serviço (DoS) será rastreada e reportada às autoridades policiais e órgãos de controle.</p>
                  <div className="bg-red-50 p-3 rounded-lg border border-red-100 text-red-900 text-xs md:text-sm">
                    <strong>2.3. Fé Pública:</strong> O usuário declara ciência de que os dados inseridos em qualquer módulo do sistema (RAT, cadastros, vistorias, alertas) possuem presunção de veracidade. A inserção de dados falsos ou a alteração maliciosa de informações públicas constitui crime de Falsidade Ideológica (Art. 299 CP) e Inserção de dados falsos em sistema de informações (Art. 313-A CP).
                  </div>
                </div>
              </Section>

              <Section icon={Lock} title="3. Propriedade Intelectual">
                <p className="mb-3">3.1. O conteúdo técnico, códigos, logotipos e bases de dados são de propriedade exclusiva do Estado de Minas Gerais.</p>
                <p>3.2. É proibida a reprodução parcial ou total dos dados para fins comerciais. O uso das informações para fins acadêmicos ou jornalísticos deve citar obrigatoriamente a fonte <strong>"Sistema de Defesa Civil - SDC/MG"</strong>.</p>
              </Section>

              <Section icon={CheckCircle2} title="4. Dever de Cuidado e Segurança">
                <p className="mb-4">O usuário obriga-se a:</p>
                <ul className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <li className="flex gap-2 bg-white p-3 rounded shadow-sm border border-slate-200 italic">
                    <span className="text-blue-600 font-bold">A)</span> Encerrar a sessão (logout) sempre que se afastar do terminal.
                  </li>
                  <li className="flex gap-2 bg-white p-3 rounded shadow-sm border border-slate-200 italic">
                    <span className="text-blue-600 font-bold">B)</span> Não utilizar senhas óbvias ou compartilhá-las com terceiros.
                  </li>
                  <li className="flex gap-2 bg-white p-3 rounded shadow-sm border border-slate-200 italic">
                    <span className="text-blue-600 font-bold">C)</span> Reportar imediatamente qualquer suspeita de comprometimento da conta.
                  </li>
                  <li className="flex gap-2 bg-white p-3 rounded shadow-sm border border-slate-200 italic">
                    <span className="text-blue-600 font-bold">D)</span> Não difundir conteúdos ilícitos, políticos ou de assédio.
                  </li>
                </ul>
              </Section>

              <Section icon={ShieldAlert} title="5. Tratamento de Dados (LGPD)">
                <p className="mb-3">5.1. O usuário atua como <strong>Operador de Dados</strong> (Lei 13.709/2018) e deve garantir a estrita confidencialidade, sendo proibido o uso desses dados para fins particulares.</p>
                <p>5.2. O descumprimento das normas de privacidade acarretará responsabilidade objetiva do agente perante o Estado.</p>
              </Section>

              <Section icon={Scale} title="6. Limitação de Responsabilidade">
                <p>A Administração empenha esforços para manter o SDC disponível 24/7. O usuário reconhece que instabilidades técnicas podem ocorrer. O Estado não se responsabiliza por danos decorrentes de falhas de conexão ou mau uso do hardware.</p>
              </Section>

              <Section icon={AlertTriangle} title="7. Sanções">
                <p className="mb-2">O descumprimento deste termo sujeita o usuário a:</p>
                <div className="flex flex-wrap gap-2 text-xs font-bold uppercase">
                  <span className="px-3 py-1 bg-red-100 text-red-700 rounded-full">Suspensão de Acesso</span>
                  <span className="px-3 py-1 bg-red-100 text-red-700 rounded-full">Processo Administrativo (PAD)</span>
                  <span className="px-3 py-1 bg-red-100 text-red-700 rounded-full">Responsabilização Civil e Criminal</span>
                </div>
              </Section>

              {!hasScrolledToBottom && (
                <div className="sticky bottom-0 left-0 right-0 flex justify-center pb-4 animate-bounce pointer-events-none">
                  <div className="bg-blue-600 text-white px-4 py-2 rounded-full text-xs flex items-center gap-2 shadow-lg">
                    Role para ler até o fim <ChevronDown size={14} />
                  </div>
                </div>
              )}
            </div>

            {/* Footer / Actions */}
            <div className="p-6 bg-white border-t border-slate-200 shrink-0">
              <div className="max-w-2xl mx-auto flex flex-col gap-4">
                <label className={`flex items-start gap-3 p-4 rounded-xl cursor-pointer transition-all ${agreed ? 'bg-blue-50 border-blue-200' : 'bg-slate-50 border-transparent'} border`}>
                  <input 
                    type="checkbox" 
                    className="mt-1 w-5 h-5 rounded border-slate-300 text-blue-700 focus:ring-blue-500"
                    checked={agreed}
                    onChange={(e) => setAgreed(e.target.checked)}
                    disabled={!hasScrolledToBottom}
                  />
                  <span className={`text-sm md:text-base ${!hasScrolledToBottom ? 'text-slate-400' : 'text-slate-700 font-medium'}`}>
                    Eu li, compreendi e concordo integralmente com os Termos de Uso e Responsabilidade do Sistema SDC.
                  </span>
                </label>

                <div className="flex flex-col sm:flex-row gap-3">
                  <button 
                    onClick={() => setIsOpen(false)}
                    className="flex-1 py-3 px-6 rounded-xl text-slate-600 font-semibold hover:bg-slate-100 transition-colors border border-slate-200"
                  >
                    Recusar e Sair
                  </button>
                  <button 
                    disabled={!agreed || !hasScrolledToBottom}
                    onClick={handleAccept}
                    className={`flex-[2] py-3 px-6 rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2
                      ${agreed && hasScrolledToBottom 
                        ? 'bg-blue-700 hover:bg-blue-800 text-white transform active:scale-95' 
                        : 'bg-slate-200 text-slate-400 cursor-not-allowed'}`}
                  >
                    {agreed ? <CheckCircle2 size={20} /> : <Lock size={20} />}
                    Aceitar e Acessar o Sistema
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Toast Notification */}
      {showToast && (
        <div className="fixed top-10 left-1/2 -translate-x-1/2 z-[60] animate-in slide-in-from-top duration-500">
          <div className="bg-emerald-600 text-white px-8 py-4 rounded-2xl shadow-2xl flex items-center gap-3">
            <CheckCircle2 size={24} />
            <div>
              <p className="font-bold">Termos Aceitos!</p>
              <p className="text-sm opacity-90">Redirecionando para o ambiente seguro...</p>
            </div>
          </div>
        </div>
      )}

      <style>{`
        .custom-scrollbar::-webkit-scrollbar {
          width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
          background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
          background: #cbd5e1;
          border-radius: 20px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
          background: #94a3b8;
        }
      `}</style>
    </div>
  );
};

export default App;