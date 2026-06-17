import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Plus, 
  Mail, 
  Trash2, 
  Edit, 
  UserCheck, 
  Clock, 
  ShieldAlert, 
  CheckCircle2, 
  X, 
  Lock, 
  Key,
  Eye,
  EyeOff,
  AlertTriangle
} from 'lucide-react';
import { MembroEquipe, PermissoesCargo, Usuario } from '../types';

interface TeamManagementProps {
  membros: MembroEquipe[];
  usuarioLogado: Usuario;
  permissoes: Record<'gerente' | 'vendedor' | 'estoquista', PermissoesCargo>;
  onAdicionarMembro: (m: Omit<MembroEquipe, 'id'>) => void;
  onAtualizarMembro: (id: number, m: Omit<MembroEquipe, 'id'>) => void;
  onExcluirMembro: (id: number) => void;
  onAtualizarPermissoes: (cargo: 'gerente' | 'vendedor' | 'estoquista', novaPerm: PermissoesCargo) => void;
  podeEditar: boolean;
}

export default function TeamManagement({
  membros,
  usuarioLogado,
  permissoes,
  onAdicionarMembro,
  onAtualizarMembro,
  onExcluirMembro,
  onAtualizarPermissoes,
  podeEditar
}: TeamManagementProps) {
  // Estado Aba Ativa (1: Membros, 2: Permissões de Cargos)
  const [panelAtiva, setPanelAtiva] = useState<'membros' | 'permissoes'>('membros');

  // Modal de Convidar / Editar Membro
  const [mostrarModalMembro, setMostrarModalMembro] = useState(false);
  const [membroEditando, setMembroEditando] = useState<MembroEquipe | null>(null);

  // Campos de membro
  const [nome, setNome] = useState('');
  const [email, setEmail] = useState('');
  const [cargo, setCargo] = useState<'gerente' | 'vendedor' | 'estoquista'>('vendedor');
  const [status, setStatus] = useState<'ativo' | 'convite_pendente'>('ativo');

  // Toasts
  const [toast, setToast] = useState<{ texto: string; tipo: 'sucesso' | 'erro' } | null>(null);
  const [membroParaExcluir, setMembroParaExcluir] = useState<MembroEquipe | null>(null);

  const exibirToast = (texto: string, tipo: 'sucesso' | 'erro' = 'sucesso') => {
    setToast({ texto, tipo });
    setTimeout(() => {
      setToast(null);
    }, 4000);
  };

  const handleSalvarMembro = (e: React.FormEvent) => {
    e.preventDefault();
    if (!nome.trim()) {
      exibirToast("Preencha o nome do membro.", "erro");
      return;
    }
    if (!email.trim() || !email.includes('@')) {
      exibirToast("Insira um endereço de e-mail válido.", "erro");
      return;
    }

    const payload = {
      nome: nameFormatted(nome.trim()),
      email: email.trim().toLowerCase(),
      cargo,
      status
    };

    if (membroEditando) {
      // RN-AC-004: "Nenhum usuário pode alterar seu próprio perfil ou permissões. Somente o administrador pode modificar dados de outros."
      if (membroEditando.nome.toLowerCase().includes(usuarioLogado.usuario) || membroEditando.id === usuarioLogado.id) {
        exibirToast("Restrição de segurança: Você não pode alterar as configurações do seu próprio perfil.", "erro");
        return;
      }

      onAtualizarMembro(membroEditando.id, payload);
      exibirToast(`Configurações de "${payload.nome}" atualizadas imediatamente!`);
    } else {
      // RF-EQ-001: Convidar por email
      onAdicionarMembro(payload);
      exibirToast(`E-mail com convite enviado com sucesso para ${payload.email}!`);
    }

    setMostrarModalMembro(false);
  };

  const nameFormatted = (str: string) => {
    return str.split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ');
  };

  const abrirAdicionarMembro = () => {
    setMembroEditando(null);
    setNome('');
    setEmail('');
    setCargo('vendedor');
    setStatus('ativo');
    setMostrarModalMembro(true);
  };

  const abrirEditarMembro = (m: MembroEquipe) => {
    if (m.id === usuarioLogado.id) {
      exibirToast("Auto-modificação proibida por diretrizes de segurança.", "erro");
      return;
    }
    setMembroEditando(m);
    setNome(m.nome);
    setEmail(m.email);
    setCargo(m.cargo);
    setStatus(m.status);
    setMostrarModalMembro(true);
  };

  const handleExcluirMembro = (m: MembroEquipe) => {
    if (m.id === usuarioLogado.id) {
      exibirToast("Você não pode deletar sua própria conta ativa.", "erro");
      return;
    }

    // RN-AC-005: "Mínimo de um administrador. A remoção do último administrador é bloqueada pelo sistema."
    const gerentesAtivos = membros.filter(mem => mem.cargo === 'gerente' && mem.status === 'ativo');
    if (m.cargo === 'gerente' && gerentesAtivos.length <= 1) {
      exibirToast("Bloqueio de segurança: A loja deve possuir pelo menos um administrador ativo.", "erro");
      return;
    }

    setMembroParaExcluir(m);
  };

  const confirmarExcluirMembro = () => {
    if (!membroParaExcluir) return;
    onExcluirMembro(membroParaExcluir.id);
    exibirToast(`Colaborador "${membroParaExcluir.nome}" removido do quadro.`);
    setMembroParaExcluir(null);
  };

  // Habilitar / Desabilitar funcionalidade por Cargo
  const alternarPermissao = (cargoAlvo: 'gerente' | 'vendedor' | 'estoquista', chave: keyof PermissoesCargo) => {
    // Impedir gerente de desativar equipe para gerente (para não perder controle)
    if (cargoAlvo === 'gerente' && chave === 'equipe') {
      exibirToast("O administrador deve possuir acesso irrestrito ao gerenciamento de equipe.", "erro");
      return;
    }

    const permissaoAtual = permissoes[cargoAlvo];
    const novaPerm = {
      ...permissaoAtual,
      [chave]: !permissaoAtual[chave]
    };

    onAtualizarPermissoes(cargoAlvo, novaPerm);
    exibirToast(`Permissão de "${chave}" atualizada para o cargo de ${cargoAlvo}!`);
  };

  return (
    <div className="space-y-6">
      
      {/* Toast Notificação */}
      <AnimatePresence>
        {toast && (
          <motion.div
            initial={{ opacity: 0, y: -50 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0 }}
            className={`fixed top-6 right-6 z-55 p-4 rounded-xl shadow-xl flex items-center gap-3 border ${
              toast.tipo === 'erro' ? 'bg-red-950 border-red-900 text-red-200' : 'bg-slate-900 border-slate-800 text-white'
            }`}
          >
            {toast.tipo === 'erro' ? <ShieldAlert className="text-red-400" size={18} /> : <CheckCircle2 className="text-sage" size={18} />}
            <span className="text-xs font-semibold">{toast.texto}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Equipe e Funções</h1>
          <p className="text-slate-500 text-sm mt-1">Configure as credenciais de segurança e as abas e permissões de acesso baseados em funções (RBAC).</p>
        </div>

        {podeEditar && panelAtiva === 'membros' && (
          <button
            onClick={abrirAdicionarMembro}
            className="bg-ebony hover:bg-[#a4ac86] text-white font-bold px-4 py-2.5 rounded-lg text-xs tracking-wider uppercase transition flex items-center gap-1.5 cursor-pointer ml-auto sm:ml-0 shadow-sm"
          >
            <Plus size={16} />
            <span>Adicionar Funcionario</span>
          </button>
        )}
      </div>

      {/* Navegação e Abas de Configuração */}
      <div className="border-b border-slate-100 flex gap-6 text-xs font-bold uppercase tracking-wider">
        <button
          onClick={() => setPanelAtiva('membros')}
          className={`pb-3 border-b-2 transition ${
            panelAtiva === 'membros' ? 'border-ebony text-slate-900' : 'border-transparent text-slate-400'
          }`}
        >
          Colaboradores ({membros.length})
        </button>
        <button
          onClick={() => setPanelAtiva('permissoes')}
          className={`pb-3 border-b-2 transition ${
            panelAtiva === 'permissoes' ? 'border-ebony text-slate-900' : 'border-transparent text-slate-400'
          }`}
        >
          Grade de Permissões (RBAC)
        </button>
      </div>

      {/* PAINEL 1: LISTAGEM DE COLABORADORES */}
      {panelAtiva === 'membros' && (
        <div className="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full border-collapse text-left text-xs font-sans">
              <thead className="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
                <tr>
                  <th className="p-4">Colaborador</th>
                  <th className="p-4">E-mail</th>
                  <th className="p-4">Cargo / Função</th>
                  <th className="p-4">Status Convite</th>
                  {podeEditar && <th className="p-4 text-center">Controles</th>}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-slate-705 font-medium">
                {membros.map((memb) => (
                  <tr key={memb.id} className="hover:bg-slate-50/40 transition">
                    <td className="p-4">
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-bone text-ebony flex items-center justify-center font-bold">
                          {memb.nome.charAt(0)}
                        </div>
                        <div>
                          <span className="font-bold text-slate-900 text-sm block">{memb.nome}</span>
                          {memb.id === usuarioLogado.id && (
                            <span className="text-[9px] font-bold text-sage uppercase border border-sage/45 px-1.5 rounded bg-sage/5">Minha Conta</span>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="p-4 text-slate-500 font-mono text-[11px]">{memb.email}</td>
                    <td className="p-4">
                      <span className="inline-flex text-[10px] uppercase font-extrabold text-slate-600 bg-slate-50 px-2 py-0.5 rounded border border-slate-200/50">
                        {memb.cargo}
                      </span>
                    </td>
                    <td className="p-4">
                      <div className="flex items-center gap-1.5">
                        {memb.status === 'ativo' ? (
                          <>
                            <UserCheck className="text-emerald-600 shrink-0" size={14} />
                            <span className="text-emerald-700 font-semibold text-[11px]">Ativo no sistema</span>
                          </>
                        ) : (
                          <>
                            <Clock className="text-amber-500 shrink-0 animate-spin" size={14} />
                            <span className="text-amber-700 font-semibold text-[11px]">Enviado - Aguardando aceitação</span>
                          </>
                        )}
                      </div>
                    </td>
                    {podeEditar && (
                      <td className="p-4 text-center">
                        {memb.id !== usuarioLogado.id ? (
                          <div className="flex items-center justify-center gap-2">
                            <button
                              onClick={() => abrirEditarMembro(memb)}
                              className="bg-stone-50 hover:bg-[#CCBFA3]/20 p-1.5 rounded text-ebony transition cursor-pointer"
                              title="Editar Perfil"
                            >
                              <Edit size={13} />
                            </button>
                            <button
                              onClick={() => handleExcluirMembro(memb)}
                              className="p-1.5 text-rose-55 hover:bg-rose-50 rounded transition cursor-pointer"
                              title="Remover Colaborador"
                            >
                              <Trash2 size={13} />
                            </button>
                          </div>
                        ) : (
                          <span className="text-xs text-slate-400 font-normal italic">Si próprio</span>
                        )}
                      </td>
                    )}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* PAINEL 2: PERMISSÕES DOS CARGOS (RBAC) */}
      {panelAtiva === 'permissoes' && (
        <div className="bg-white rounded-xl border border-slate-100 shadow-sm p-6 space-y-6">
          <div>
            <h3 className="text-base font-serif text-slate-900 font-bold">Grade de Disponibilidade de Módulos</h3>
            <p className="text-slate-400 text-xs">Ative ou revogue a visualização dos módulos específicos para as pessoas cadastradas na loja.</p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {(['gerente', 'vendedor', 'estoquista'] as const).map((cargoChave) => {
              const perm = permissoes[cargoChave];

              return (
                <div key={cargoChave} className="bg-slate-50/55 p-5 rounded-2xl border border-slate-200/50 space-y-4">
                  
                  {/* Header Cargo */}
                  <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                    <div className="text-left font-serif capitalize">
                      <h4 className="font-bold text-slate-950 text-base">{cargoChave}</h4>
                      <p className="text-[10px] text-slate-400 font-bold uppercase tracking-widest font-sans">Nível Funcional</p>
                    </div>
                    <Lock size={16} className="text-slate-400" />
                  </div>

                  {/* Lista de Permissões com Botões toggles */}
                  <div className="space-y-2.5 text-xs text-slate-700">
                    {Object.keys(perm).map((pKeyRaw) => {
                      const pKey = pKeyRaw as keyof PermissoesCargo;
                      const ativo = perm[pKey];

                      return (
                        <div key={pKey} className="flex items-center justify-between p-2 hover:bg-white rounded-lg transition">
                          <span className="capitalize font-semibold text-slate-700">{pKey}</span>
                          
                          <button
                            disabled={!podeEditar || (cargoChave === 'gerente' && pKey === 'equipe')}
                            onClick={() => alternarPermissao(cargoChave, pKey)}
                            className={`px-2.5 py-1 rounded text-[10px] font-bold transition flex items-center gap-1 ${
                              ativo 
                                ? 'bg-emerald-50 text-emerald-800 border border-emerald-200/50 hover:bg-emerald-100' 
                                : 'bg-red-50 text-red-800 border border-red-200/50 hover:bg-red-100'
                            } disabled:opacity-50 disabled:hover:bg-emerald-50 cursor-pointer`}
                          >
                            {ativo ? (
                              <>
                                <Eye size={12} />
                                <span>Liberado</span>
                              </>
                            ) : (
                              <>
                                <EyeOff size={12} />
                                <span>Bloqueado</span>
                              </>
                            )}
                          </button>
                        </div>
                      );
                    })}
                  </div>

                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* MODAL CONVIDAR / EDITAR MEMBRO */}
      <AnimatePresence>
        {mostrarModalMembro && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMostrarModalMembro(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-xl shadow-xl overflow-hidden z-10 p-6 space-y-4 text-left font-sans text-xs"
            >
              <div>
                <h3 className="text-base font-bold text-slate-900 font-serif">
                  {membroEditando ? "Editar Perfil do Colaborador" : "Enviar Convite para Novo Integrante"}
                </h3>
                <p className="text-slate-400 text-xs">O colaborador receberá o link por e-mail para acesso.</p>
              </div>

              <form onSubmit={handleSalvarMembro} className="space-y-4">
                <div>
                  <label className="block font-bold text-slate-550 mb-1 uppercase">Nome do Colaborador *</label>
                  <input
                    type="text"
                    required
                    value={nome}
                    onChange={(e) => setNome(e.target.value)}
                    placeholder="Ex: Carlos Albuquerque"
                    className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs focus:ring-1 focus:ring-ebony"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-550 mb-1 uppercase">E-mail Corporativo *</label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-3 text-slate-400" size={14} />
                    <input
                      type="email"
                      required
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="colaborador@elegancia.com"
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg pl-9 pr-3 py-2.5 text-slate-800 text-xs focus:ring-1 focus:ring-ebony"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block font-bold text-slate-550 mb-1 uppercase">Cargo Atribuído</label>
                    <select
                      value={cargo}
                      onChange={(e) => setCargo(e.target.value as any)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2 text-slate-800 font-semibold focus:outline-none"
                    >
                      <option value="gerente">Gerente / Administrador</option>
                      <option value="vendedor">Vendedor / Caixa</option>
                      <option value="estoquista">Estoquista / Logística</option>
                    </select>
                  </div>

                  <div>
                    <label className="block font-bold text-slate-550 mb-1 uppercase">Status de Registro</label>
                    <select
                      value={status}
                      onChange={(e) => setStatus(e.target.value as any)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2 text-slate-800 font-semibold focus:outline-none"
                    >
                      <option value="convite_pendente">Aguardando Convite</option>
                      <option value="ativo">Ativo Imediatamente</option>
                    </select>
                  </div>
                </div>

                <div className="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setMostrarModalMembro(false)}
                    className="px-4 py-2 bg-stone-55 text-stone-605 hover:bg-stone-100 rounded-lg font-bold transition uppercase"
                  >
                    Retroceder
                  </button>
                  <button
                    type="submit"
                    className="px-5 py-2 bg-ebony hover:bg-reseda text-white rounded-lg font-bold transition uppercase tracking-wider"
                  >
                    Confirmar Envio
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL CONFIRMAÇÃO EXCLUSÃO MEMBRO DA EQUIPE */}
      <AnimatePresence>
        {membroParaExcluir && (
          <div className="fixed inset-0 z-55 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMembroParaExcluir(null)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden z-10 p-6 space-y-5 text-slate-800 text-left font-sans"
            >
              <div className="text-center space-y-2">
                <div className="mx-auto w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center">
                  <AlertTriangle className="text-[#a64b2a]" size={22} />
                </div>
                <h3 className="text-lg font-serif text-slate-900 font-semibold">Excluir Colaborador</h3>
                <p className="text-slate-500 text-xs leading-relaxed">
                  Deseja realmente remover o colaborador <strong className="text-rose-900">"{membroParaExcluir.nome}"</strong> ({membroParaExcluir.cargo})? Todos os acessos e permissões vinculados serão revogados imediatamente de forma definitiva.
                </p>
              </div>

              <div className="flex items-center justify-center gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setMembroParaExcluir(null)}
                  className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-650 rounded-xl transition text-xs font-semibold cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="button"
                  onClick={confirmarExcluirMembro}
                  className="w-full py-2.5 bg-[#a64b2a] hover:bg-[#8b2611] text-white rounded-xl font-bold transition text-xs uppercase tracking-wider cursor-pointer"
                >
                  Sim, Excluir
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
}
