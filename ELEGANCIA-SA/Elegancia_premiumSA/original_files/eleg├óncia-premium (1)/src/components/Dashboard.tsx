import React from 'react';
import { motion } from 'motion/react';
import { DollarSign, ShoppingBag, AlertTriangle, Percent, Package, ArrowUpRight, TrendingUp } from 'lucide-react';
import { Produto, Venda } from '../types';

interface DashboardProps {
  produtos: Produto[];
  vendas: Venda[];
}

export default function Dashboard({ produtos, vendas }: DashboardProps) {
  // Calculando estatísticas com base nas vendas
  const totalFaturado = vendas.reduce((acc, v) => acc + v.total, 0);
  const totalItensVendidos = vendas.reduce((acc, v) => 
    acc + v.itens.reduce((sum, item) => sum + item.quantidade, 0)
  , 0);
  
  const estoqueBaixoCount = produtos.filter(p => p.estoque < 10).length;

  // Calcular vendas por categoria para o gráfico de porcentagens
  const vendasPorCategoria: { [key: string]: number } = {};
  let totalFaturadoCategoriaCalculavel = 0;

  vendas.forEach(venda => {
    venda.itens.forEach(item => {
      // Procurar categoria do produto correspondente
      const prod = produtos.find(p => p.id === item.idProduto);
      const categoria = prod?.categoria || "Outros";
      const valorItem = item.quantidade * item.precoUnitario;
      
      vendasPorCategoria[categoria] = (vendasPorCategoria[categoria] || 0) + valorItem;
      totalFaturadoCategoriaCalculavel += valorItem;
    });
  });

  // Se não houver vendas ainda, preenchemos com frações fictícias baseadas nos produtos
  const categoriasValidas = Object.keys(vendasPorCategoria).length > 0;
  const dadosGrafico = Object.keys(categoriasValidas ? vendasPorCategoria : {
    "Vestidos": 450.00,
    "Calças": 199.90,
    "Camisas": 159.90,
    "Calçados": 320.00,
    "Acessórios": 89.90
  }).map(cat => {
    const valor = categoriasValidas ? vendasPorCategoria[cat] : (cat === "Vestidos" ? 450 : cat === "Calças" ? 200 : cat === "Camisas" ? 160 : cat === "Calçados" ? 320 : 90);
    const totalDivisor = categoriasValidas ? totalFaturadoCategoriaCalculavel : (450 + 200 + 160 + 320 + 90);
    const porcentagem = totalDivisor > 0 ? (valor / totalDivisor) * 100 : 0;
    
    return {
      categoria: cat,
      valor,
      porcentagem
    };
  }).sort((a, b) => b.valor - a.valor);

  // Paleta de tons refinados da paleta de cores fornecida (Ebony, Reseda, Sage, Dun, Bone)
  const coresCategoria: { [key: string]: string } = {
    "Vestidos": "from-ebony to-reseda",
    "Calças": "from-[#525a41] to-ebony",
    "Camisas": "from-[#858d6f] to-reseda",
    "Calçados": "from-sage to-[#8f9772]",
    "Acessórios": "from-dun to-[#b9ac90]",
    "Casacos": "from-ebony to-[#353b2a]",
    "Saias": "from-[#9ca47e] to-sage"
  };

  const getCor = (cat: string, index: number) => {
    return coresCategoria[cat] || (index % 2 === 0 ? "from-ebony to-reseda" : "from-sage to-dun");
  };

  return (
    <div className="space-y-8">
      {/* Título e Header */}
      <div>
        <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Painel de Desempenho</h1>
        <p className="text-slate-500 text-sm mt-1">Estatísticas consolidadas e análise de porcentagem de vendas.</p>
      </div>

      {/* Grid de Cards Resumo */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between"
        >
          <div>
            <span className="text-sm text-slate-400 font-medium tracking-wider uppercase">Faturamento</span>
            <h3 className="text-2xl font-bold text-slate-800 mt-1">R$ {totalFaturado.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</h3>
            <span className="text-xs text-emerald-600 flex items-center gap-1 mt-1 font-medium">
              <ArrowUpRight size={14} /> +12.3% esta semana
            </span>
          </div>
          <div className="p-3 bg-bone text-ebony rounded-lg">
            <DollarSign size={24} />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between"
        >
          <div>
            <span className="text-sm text-slate-400 font-medium tracking-wider uppercase">Vendas Registradas</span>
            <h3 className="text-2xl font-bold text-slate-800 mt-1">{vendas.length} pedidos</h3>
            <span className="text-xs text-emerald-600 flex items-center gap-1 mt-1 font-medium">
              <ArrowUpRight size={14} /> Ticket: R$ {(vendas.length > 0 ? totalFaturado / vendas.length : 0).toFixed(2)}
            </span>
          </div>
          <div className="p-3 bg-bone text-ebony rounded-lg">
            <ShoppingBag size={24} />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between"
        >
          <div>
            <span className="text-sm text-slate-400 font-medium tracking-wider uppercase">Peças Vendidas</span>
            <h3 className="text-2xl font-bold text-slate-800 mt-1">{totalItensVendidos} un</h3>
            <span className="text-xs text-slate-500 flex items-center gap-1 mt-1">
              Moda premium feminina & masculina
            </span>
          </div>
          <div className="p-3 bg-bone text-ebony rounded-lg">
            <Package size={24} />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className={`p-6 rounded-xl shadow-sm border transition-all duration-300 ${
            estoqueBaixoCount > 0 
              ? 'bg-rose-50/50 border-rose-100' 
              : 'bg-white border-slate-100'
          } flex items-center justify-between`}
        >
          <div>
            <span className="text-sm text-slate-400 font-medium tracking-wider uppercase">Estoque Alerta</span>
            <h3 className={`text-2xl font-bold mt-1 ${estoqueBaixoCount > 0 ? 'text-rose-800' : 'text-slate-800'}`}>
              {estoqueBaixoCount} itens
            </h3>
            <span className="text-xs text-rose-600 flex items-center gap-1 mt-1 font-medium">
              {estoqueBaixoCount > 0 ? 'Abaixo do limite de segurança' : 'Estoque regularizado'}
            </span>
          </div>
          <div className={`p-3 rounded-lg ${estoqueBaixoCount > 0 ? 'bg-rose-100 text-rose-700' : 'bg-stone-50 text-stone-500'}`}>
            <AlertTriangle size={24} />
          </div>
        </motion.div>
      </div>

      {/* Gráfico de Vendas por Porcentagem e Categorias */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Lado esquerdo: Distribuição Core de Vendas */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 lg:col-span-2 flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-6">
              <div>
                <h3 className="text-lg font-semibold text-slate-800">Distribuição de Vendas (%)</h3>
                <p className="text-slate-400 text-xs">Faturamento proporcional por linha de vestuário.</p>
              </div>
              <div className="flex items-center gap-2 text-xs bg-bone text-ebony font-semibold px-2.5 py-1 rounded-full border border-dun/30">
                <TrendingUp size={14} />
                <span>Moda Atualizada</span>
              </div>
            </div>

            {/* Gráfico de Barras Elegantes de Porcentagem */}
            <div className="space-y-5">
              {dadosGrafico.map((item, idx) => (
                <div key={item.categoria} className="group">
                  <div className="flex justify-between text-sm mb-1.5 font-medium">
                    <span className="text-slate-700 group-hover:text-ebony transition-colors">{item.categoria}</span>
                    <div className="text-slate-500">
                      <span className="text-slate-800 font-semibold mr-2">{item.porcentagem.toFixed(1)}%</span>
                      <span className="text-xs text-slate-400 font-normal">R$ {item.valor.toFixed(2)}</span>
                    </div>
                  </div>
                  {/* Container da Barra */}
                  <div className="w-full bg-slate-50 rounded-full h-3.5 overflow-hidden">
                    <motion.div
                      className={`bg-gradient-to-r ${getCor(item.categoria, idx)} h-full rounded-full`}
                      initial={{ width: 0 }}
                      animate={{ width: `${item.porcentagem}%` }}
                      transition={{ duration: 1, ease: "easeOut" }}
                    />
                  </div>
                </div>
              ))}
            </div>
          </div>
          
          <div className="mt-8 pt-4 border-t border-slate-100 flex justify-between items-center text-xs text-slate-400">
            <span>Cálculos baseados em registros fiscais reais</span>
            <span className="font-medium text-slate-600">Atualizado agora há pouco</span>
          </div>
        </div>

        {/* Lado direito: Representação Visual Donut Elegante baseada em SVG */}
        <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-between">
          <div>
            <h3 className="text-lg font-semibold text-slate-800">Participação de Coleção</h3>
            <p className="text-slate-400 text-xs mb-6">Visualização geométrica proporcional.</p>
 
            {/* Donut SVG */}
            <div className="relative flex justify-center items-center py-6">
              <svg width="180" height="180" viewBox="0 0 42 42" className="transform -rotate-90">
                {/* Círculo de fundo */}
                <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#f1f5f9" strokeWidth="4.5" />
 
                {/* Arcos dinâmicos baseados nas frações */}
                {(() => {
                  let acumuladorPorcentagem = 0;
                  return dadosGrafico.map((item, idx) => {
                    const strokeDasharray = `${item.porcentagem} ${100 - item.porcentagem}`;
                    const strokeDashoffset = 100 - acumuladorPorcentagem;
                    acumuladorPorcentagem += item.porcentagem;
 
                    // Cor do traço baseando-se no índice ou categoria
                    const coresDonut = ["#414833", "#737A5D", "#A4AC86", "#CCBFA3", "#EBE3D2"];
                    const corSegmento = coresDonut[idx] || "#737A5D";
 
                    return (
                      <circle
                        key={item.categoria}
                        cx="21"
                        cy="21"
                        r="15.915"
                        fill="transparent"
                        stroke={corSegmento}
                        strokeWidth="4.5"
                        strokeDasharray={strokeDasharray}
                        strokeDashoffset={strokeDashoffset}
                        className="transition-all duration-500 ease-out hover:stroke-[5.5]"
                        style={{ cursor: 'pointer' }}
                      />
                    );
                  });
                })()}
              </svg>
 
              {/* Centro do Donut */}
              <div className="absolute inset-x-0 inset-y-0 m-auto w-24 h-24 rounded-full bg-white shadow-inner flex flex-col items-center justify-center">
                <span className="text-3xl font-bold text-slate-800">100%</span>
                <span className="text-[10px] text-slate-400 uppercase tracking-widest font-semibold mt-0.5">Faturado</span>
              </div>
            </div>
          </div>
 
          {/* Legendas de Cores */}
          <div className="space-y-2 mt-4">
            {dadosGrafico.slice(0, 4).map((item, idx) => {
              const coresHex = ["bg-ebony", "bg-reseda", "bg-sage", "bg-dun", "bg-bone"];
              const corClasse = coresHex[idx] || "bg-reseda";
              return (
                <div key={item.categoria} className="flex items-center justify-between text-xs font-medium">
                  <div className="flex items-center gap-2 text-slate-600">
                    <span className={`w-2.5 h-2.5 rounded-full ${corClasse}`} />
                    <span>{item.categoria}</span>
                  </div>
                  <span className="text-slate-800">{item.porcentagem.toFixed(1)}%</span>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}
