import React, { useState } from 'react';
import { 
  LayoutDashboard, 
  FileText, 
  CheckSquare, 
  File, 
  Scale, 
  Heart, 
  Building2, 
  Folder, 
  GraduationCap, 
  Cloud, 
  ClipboardCheck, 
  Lock,
  Search,
  X,
  ChevronRight,
  Info,
  ExternalLink,
  BookOpen
} from 'lucide-react';

const App = () => {
  const [activeModule, setActiveModule] = useState('rat');
  const [searchTerm, setSearchTerm] = useState('');

  // Dados baseados na sua barra lateral (image_97eaa7.png)
  const categories = [
    {
      title: "Principal",
      modules: [
        { id: 'visao-geral', label: 'Visão Geral', icon: LayoutDashboard, desc: 'Painel de controle com indicadores estratégicos e resumo de atividades em tempo real.' },
        { id: 'rat', label: 'RAT', icon: FileText, desc: 'Relatório de Assistência Técnica. Utilizado para documentar vistorias, pareceres e atendimentos de campo.' },
        { id: 'demandas', label: 'Demandas', icon: CheckSquare, desc: 'Gestão de tarefas e solicitações internas ou externas que requerem acompanhamento.' },
        { id: 'pae', label: 'PAE', icon: File, desc: 'Plano de Ação de Emergência. Estruturação de respostas para cenários de risco iminente.' },
      ]
    },
    {
      title: "Módulos de Gestão",
      modules: [
        { id: 'decretacoes', label: 'Decretações', icon: Scale, desc: 'Formalização de situações de emergência ou estado de calamidade pública.' },
        { id: 'ajuda', label: 'Ajuda Humanitária', icon: Heart, desc: 'Gestão de donativos, logística de suprimentos e assistência a populações afetadas.' },
        { id: 'orgaos', label: 'Órgãos', icon: Building2, desc: 'Cadastro e integração de entidades parceiras e órgãos da administração pública.' },
        { id: 'tdap', label: 'TDAP', icon: Folder, desc: 'Transferência de Dados de Apoio. Repositório de documentos técnicos e normativos.' },
        { id: 'treinamento', label: 'Treinamento', icon: GraduationCap, desc: 'Gestão de cursos, capacitações e simulados para as equipas de defesa civil.' },
        { id: 'meteorologia', label: 'Meteorologia', icon: Cloud, desc: 'Monitoramento de condições climáticas, alertas de chuva e riscos geo-hidrológicos.' },
        { id: 'vistoria', label: 'Vistoria', icon: ClipboardCheck, desc: 'Módulo específico para inspeções técnicas de engenharia e avaliação de danos.' },
      ]
    },
    {
      title: "Administração",
      modules: [
        { id: 'permissao', label: 'Permissionamento', icon: Lock, desc: 'Controle de perfis de acesso, hierarquias e segurança de dados do sistema.' },
      ]
    }
  ];

  // Flatten para busca e seleção
  const allModules = categories.flatMap(c => c.modules);
  const currentModule = allModules.find(m => m.id === activeModule) || allModules[0];

  return (
    <div className="min-h-screen bg-slate-900/40 flex items-center justify-center p-4 font-sans">
      <div className="bg-white rounded-3xl shadow-2xl w-full max-w-6xl flex overflow-hidden h-[750px] animate-in fade-in zoom-in duration-300">
        
        {/* Sidebar com Lista de Módulos */}
        <aside className="w-80 bg-slate-50 border-r border-slate-200 flex flex-col">
          <div className="p-6 border-b border-slate-200 bg-white">
            <div className="flex items-center gap-3 mb-4">
              <div className="p-2 bg-blue-600 rounded-xl shadow-lg shadow-blue-200">
                <BookOpen className="text-white w-5 h-5" />
              </div>
              <div>
                <h2 className="text-lg font-bold text-slate-800 leading-tight">Guia do Sistema</h2>
                <p className="text-[10px] text-slate-400 uppercase font-bold tracking-widest">Manual de Módulos</p>
              </div>
            </div>
            
            <div className="relative group">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-600 transition-colors" size={16} />
              <input 
                type="text" 
                placeholder="Buscar módulo..."
                className="w-full pl-10 pr-4 py-2 bg-slate-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all"
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>

          <nav className="flex-1 overflow-y-auto p-4 space-y-6">
            {categories.map((cat, idx) => (
              <div key={idx} className="space-y-2">
                <h3 className="px-4 text-[11px] font-black text-slate-400 uppercase tracking-[0.1em]">{cat.title}</h3>
                <div className="space-y-1">
                  {cat.modules
                    .filter(m => m.label.toLowerCase().includes(searchTerm.toLowerCase()))
                    .map((mod) => (
                    <button
                      key={mod.id}
                      onClick={() => setActiveModule(mod.id)}
                      className={`w-full flex items-center justify-between px-4 py-2.5 rounded-xl transition-all duration-200 text-sm font-medium ${
                        activeModule === mod.id 
                          ? 'bg-blue-600 text-white shadow-md shadow-blue-100' 
                          : 'text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm border border-transparent hover:border-slate-200'
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <mod.icon size={18} />
                        {mod.label}
                      </div>
                      {activeModule === mod.id && <ChevronRight size={14} className="animate-in slide-in-from-left-2" />}
                    </button>
                  ))}
                </div>
              </div>
            ))}
          </nav>

          <div className="p-4 bg-slate-100/50 border-t border-slate-200">
            <button className="w-full flex items-center justify-center gap-2 py-3 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
              <Info size={14} />
              Central de Chamados
            </button>
          </div>
        </aside>

        {/* Área Principal de Explicação */}
        <main className="flex-1 flex flex-col bg-white">
          <header className="p-8 flex justify-between items-start border-b border-slate-50">
            <div className="flex items-center gap-5">
              <div className="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <currentModule.icon size={40} className="text-blue-600" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <span className="px-2 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-bold rounded uppercase tracking-wider">Módulo Ativo</span>
                  <h1 className="text-3xl font-black text-slate-800 tracking-tight">{currentModule.label}</h1>
                </div>
                <p className="text-slate-500 mt-1 font-medium italic">Entenda como utilizar esta ferramenta no SDC MG.</p>
              </div>
            </div>
            <button className="p-2 hover:bg-slate-100 rounded-full text-slate-300 transition-colors">
              <X size={24} />
            </button>
          </header>

          <div className="flex-1 overflow-y-auto p-12 space-y-10">
            {/* O Que é */}
            <section className="animate-in fade-in slide-in-from-bottom-4 duration-500">
              <h4 className="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                <div className="w-4 h-[2px] bg-blue-600"></div>
                O que é este módulo?
              </h4>
              <p className="text-lg text-slate-600 leading-relaxed font-medium">
                {currentModule.desc}
              </p>
            </section>

            {/* Funcionalidades FAQ */}
            <section className="animate-in fade-in slide-in-from-bottom-6 duration-600 delay-100">
              <h4 className="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Principais Funcionalidades</h4>
              <div className="grid grid-cols-2 gap-6">
                {[
                  { q: "Quem pode acessar?", a: "Disponível para perfis de nível Gestor e Coordenador Estadual." },
                  { q: "Quais dados são gerados?", a: "Relatórios analíticos exportáveis em PDF e tabelas CSV." },
                  { q: "Integração", a: "Este módulo comunica-se diretamente com o Permissionamento e RAT." },
                  { q: "Frequência de Uso", a: "Uso diário para monitoramento e resposta a incidentes." }
                ].map((item, i) => (
                  <div key={i} className="p-6 bg-slate-50 rounded-2xl border border-slate-100 hover:border-blue-200 hover:bg-blue-50/30 transition-all cursor-default group">
                    <h5 className="font-bold text-slate-800 mb-2 flex items-center gap-2">
                      <div className="w-1.5 h-1.5 rounded-full bg-blue-500"></div>
                      {item.q}
                    </h5>
                    <p className="text-sm text-slate-500 leading-snug group-hover:text-slate-600">{item.a}</p>
                  </div>
                ))}
              </div>
            </section>

            {/* Dica do Especialista */}
            <div className="p-8 bg-indigo-600 rounded-3xl text-white flex items-center gap-6 shadow-xl shadow-indigo-100 animate-in fade-in slide-in-from-bottom-8 duration-700 delay-200">
              <div className="p-4 bg-white/10 rounded-2xl backdrop-blur-md">
                <Info size={32} />
              </div>
              <div>
                <h4 className="font-bold text-lg">Dica de Utilização</h4>
                <p className="text-indigo-100 text-sm mt-1 leading-relaxed">
                  Para obter melhores resultados no módulo de <b>{currentModule.label}</b>, certifique-se de que os dados geográficos estão atualizados nas Configurações de Regionalização.
                </p>
              </div>
            </div>
          </div>

          <footer className="p-8 border-t border-slate-100 flex items-center justify-between">
            <div className="flex gap-4">
               <button className="flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-slate-600 transition-colors">
                <BookOpen size={16} /> Ver Documentação Completa
               </button>
            </div>
            <button className="flex items-center gap-2 px-6 py-3 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-900 transition-all active:scale-95">
              Abrir Módulo de {currentModule.label}
              <ExternalLink size={16} />
            </button>
          </footer>
        </main>
      </div>
    </div>
  );
};

export default App;