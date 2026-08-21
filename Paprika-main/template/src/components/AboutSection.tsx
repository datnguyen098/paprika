import React, { useState } from 'react';
import { motion } from 'motion/react';
import { 
  Flame, 
  MapPin, 
  Sparkles, 
  Heart, 
  Calendar, 
  CheckCircle, 
  UtensilsCrossed, 
  Award, 
  Clock, 
  ShieldCheck, 
  ChevronRight, 
  Layers 
} from 'lucide-react';

interface AboutSectionProps {
  setCurrentPage: (page: string) => void;
}

export default function AboutSection({ setCurrentPage }: AboutSectionProps) {
  const [activeTimeline, setActiveTimeline] = useState<number>(2021);

  const timelineEvents = [
    {
      year: 2021,
      title: "Khởi nguồn từ Athens",
      subtitle: "The Humble Greek Food Truck",
      description: "Hành trình bắt đầu từ một chiếc xe tải thực phẩm nhỏ trên các góc phố nhộn nhịp ở Athens. Dimitris - người sáng lập của chúng tôi đã ấp ủ ước mơ mang phong cách nướng vỉ nguyên bản giao thoa cùng nước sốt cay đậm vị mộc mạc."
    },
    {
      year: 2023,
      title: "Sự ra đời của Verde Chilli",
      subtitle: "The Fusion Revolution",
      description: "Thương hiệu Verde Chilli chính thức được thành lập. Bằng việc kết hợp nguyên liệu thảo mộc xanh (Verde) của Địa Trung Hải cùng sự cay nồng đậm của Ớt đỏ (Chilli), chúng tôi đã tạo nên một khái niệm ẩm thực bùng nổ, gây bão thực khách Athens."
    },
    {
      year: 2025,
      title: "Thương hiệu Xuất sắc",
      subtitle: "The Culinary Masterpiece Award",
      description: "Được bình chọn là 'Nhà hàng fusion được yêu thích nhất' bởi cộng đồng ẩm thực thành phố. Chúng tôi khai trương thêm 3 chi nhánh trung tâm và ra mắt hệ thống đặt bàn trực tuyến tiện lợi phục vụ hàng ngàn lượt khách mỗi ngày."
    },
    {
      year: 2026,
      title: "Cam kết Bền vững",
      subtitle: "Eco-Friendly Delivery Future",
      description: "Cho đến nay, Verde Chilli tự hào áp dụng 100% bao bì có khả năng phân hủy sinh học, tối ưu hóa quy trình giao hàng không phát thải, mang đến các bữa ăn ngon sạch trọn vẹn cả về hương vị lẫn trách nhiệm môi trường."
    }
  ];

  const teamMembers = [
    {
      name: "Dimitris Vardis",
      role: "Bếp Trưởng Sáng Lập (Founding Executive Chef)",
      bio: "Hơn 15 năm làm việc tại các nhà hàng đạt sao Michelin tại Hy Lạp và Tây Ban Nha. Anh là người phát minh công thức 'Chili Lava Sauce' bí truyền ngon nức tiếng.",
      avatar: "🍳",
      color: "bg-amber-105 border-amber-400 text-amber-800"
    },
    {
      name: "Eleni Pappas",
      role: "Giám Đốc Dinh Dưỡng & Chất Lượng",
      bio: "Thạc sĩ Khoa học dinh dưỡng trường Đại học Athens. Cô chịu trách nhiệm cân đối nguồn calorie và đảm bảo quy tắc an toàn dị ứng nghiêm ngặt cho thực đơn.",
      avatar: "🥗",
      color: "bg-emerald-105 border-emerald-400 text-emerald-800"
    },
    {
      name: "Alex Rios",
      role: "Chuyên Gia Nướng Lửa Hồng (Fire-grill Master)",
      bio: "Đến từ Mexico, Alex mang ngọn lửa ẩm thực vùng Trung Mỹ hòa quyện vào vỉ nướng than củi Athens, giúp từng thớ thịt giữ nguyên độ căng mọng mướt.",
      avatar: "🔥",
      color: "bg-rose-105 border-rose-400 text-rose-800"
    }
  ];

  const ingredientsShowcase = [
    { title: "Bột Ớt Cayenne Độc Quyền", desc: "Được tuyển chọn từ các trang trại hữu cơ và sấy mộc truyền thống.", emoji: "🫑" },
    { title: "Bơ Hass Avocado Kem Lạnh", desc: "Xay nhuyễn mịn màng cùng nước cốt chanh tươi Địa Trung Hải mỗi sáng sớm.", emoji: "🥑" },
    { title: "Phô Mai Cheddar Lá Nguyên", desc: "Tan chảy êm mượt ôm trọn từng lớp bánh Brioche nướng giòn.", emoji: "🧀" },
    { title: "Sốt Chimichurri Thảo Mộc", desc: "Gồm rau mùi tây chín, dầu oliu nguyên chất vùng Peloponnese và tỏi cô đơn.", emoji: "🌿" }
  ];

  return (
    <div className="bg-[#FDFBF7] py-6 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-16 text-stone-900 animate-fadeIn" id="about-section-container">
      
      {/* 1. HERO HEADER WITH SOPHISTICATED TYPOGRAPHY */}
      <section className="text-center max-w-3xl mx-auto space-y-4" id="about-hero">
        <div className="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-[#B91C1C]/10 text-[#B91C1C] text-[10px] font-black uppercase tracking-widest">
          <Flame className="w-3 h-3 text-[#FFD700]" />
          About Verde Chilli
        </div>
        <h1 className="text-3xl sm:text-5xl font-black italic uppercase tracking-tight text-[#064E3B] leading-none">
          Bản Giao Hưởng <span className="text-[#B91C1C]">Hương Vị</span> Địa Trung Hải
        </h1>
        <p className="text-sm sm:text-base text-stone-550 leading-relaxed font-medium">
          Chúng tôi không chỉ bán thức ăn nhanh. Chúng tôi kiến tạo những trải nghiệm ẩm thực độc bản nướng lửa hồng, nơi giao thoa tuyệt vời giữa sự thanh tươi Địa Trung Hải của Hy Lạp và vị cay bùng nổ cuốn hút không lối thoát.
        </p>
      </section>

      {/* 2. BENTO GRID OF CORE VALUES & EXPERIENCES */}
      <section className="grid grid-cols-1 md:grid-cols-3 gap-6" id="about-bento-grid">
        
        {/* Bento Item 1: Our Core Craft */}
        <div className="md:col-span-2 bg-[#064E3B] text-white rounded-3xl p-8 flex flex-col justify-between space-y-8 relative overflow-hidden shadow-xl border border-[#043427]">
          <div className="absolute top-0 right-0 translate-x-12 -translate-y-12 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />
          
          <div className="space-y-4">
            <span className="bg-white/10 text-[#FFD700] text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded w-fit block">
              Triết lý Ẩm thực (Our Philosophy)
            </span>
            <h2 className="text-2xl sm:text-3xl font-black uppercase italic leading-tight">
              Tâm Huỳnh Trong Từng Thớ Thịt & Nước Sốt
            </h2>
            <p className="text-white/80 text-xs sm:text-sm leading-relaxed">
              Mỗi chiếc bánh Burger, từng miếng gà giòn rụm hay phần khoai tây muối tiêu tại Verde Chilli đều tuân thủ nguyên tắc thủ công. Chúng tôi tẩm ướp thịt trong suốt 12 giờ vàng ngọc với hỗn hợp thảo mộc thơm lành và dầu oliu nguyên chất, đảm bảo độ chín mọng hoàn hảo khi nướng trên ngọn lửa hồng rắc ớt cay cay độc đáo.
            </p>
          </div>

          <div className="flex gap-6 pt-4 border-t border-white/10 text-[11px] font-bold uppercase tracking-widest text-[#FFD700]">
            <div className="flex items-center gap-1.5">
              <CheckCircle className="w-4 h-4 text-[#FFD700]" />
              <span>100% Tươi Ngon mỗi ngày</span>
            </div>
            <div className="flex items-center gap-1.5">
              <UtensilsCrossed className="w-4 h-4 text-[#FFD700]" />
              <span>Bếp Nướng Than Củi Nguyên Bản</span>
            </div>
          </div>
        </div>

        {/* Bento Item 2: Safe is Sexy */}
        <div className="bg-white border border-stone-200 rounded-3xl p-8 flex flex-col justify-between space-y-6 shadow-md">
          <div className="p-3 bg-red-50 text-[#B91C1C] rounded-2xl w-fit">
            <ShieldCheck className="w-6 h-6" />
          </div>

          <div className="space-y-2">
            <h3 className="text-lg font-black uppercase text-stone-900 leading-tight">
              An Toàn & Dị Ứng Được Kiểm Soát
            </h3>
            <p className="text-stone-500 text-xs leading-relaxed">
              Kế thừa và lấy cảm hứng từ cấu trúc minh bạch của các chuỗi đồ ăn lớn nhất thế giới như kfc.gr, Verde Chilli là bên tiên phong tại Athens công khai 100% bảng chỉ số dinh dưỡng, calorie đo đạc và thành phần gây dị ứng trên từng món ăn, bảo vệ an toàn cho cả gia đình bạn.
            </p>
          </div>

          <div className="text-[10px] font-extrabold uppercase tracking-wider text-rose-700 bg-rose-50 px-3.5 py-2 rounded-xl border border-rose-100 flex items-center justify-between">
            <span>Yên tâm thưởng thức trọn vẹn</span>
            <Award className="w-4 h-4" />
          </div>
        </div>

      </section>

      {/* 3. INGREDIENTS BEAUTY MATRIX SHOWCASE */}
      <section className="space-y-6" id="ingredients-matrix">
        <div className="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
          <div className="space-y-1">
            <span className="text-[10px] font-black uppercase tracking-wider text-[#B91C1C]">Premium Quality</span>
            <h3 className="text-2xl sm:text-3xl font-black italic uppercase text-[#064E3B]">Từ Trang Trại Đến Bàn Ăn</h3>
          </div>
          <p className="text-stone-500 text-xs sm:max-w-md">
            Chúng tôi tự hào kết nối với các hợp tác xã nông nghiệp địa phương bao quanh vùng đồng bằng Attica để thu hái nguyên liệu mộc tươi non nhất.
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {ingredientsShowcase.map((ing, idx) => (
            <div key={idx} className="bg-white border border-stone-200 rounded-2xl p-5 hover:border-[#064E3B] transition-all group duration-300 shadow-sm hover:shadow-md">
              <span className="text-3xl mb-3 block group-hover:scale-110 transition-transform origin-left">{ing.emoji}</span>
              <h4 className="font-extrabold text-stone-900 text-sm mb-1">{ing.title}</h4>
              <p className="text-stone-500 text-xs leading-relaxed">{ing.desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* 4. INTERACTIVE TIMELINE WORKSPACE */}
      <section className="bg-stone-100/60 border border-stone-200/80 rounded-3xl p-6 sm:p-10 space-y-8" id="about-timeline-workspace">
        <div className="text-center max-w-xl mx-auto space-y-2">
          <span className="text-[10px] font-black uppercase tracking-wider text-stone-500">Milestones</span>
          <h3 className="text-2xl sm:text-3xl font-black italic uppercase text-stone-900">Hành Trình Kiến Tạo</h3>
          <p className="text-stone-500 text-xs">Hãy click vào từng mốc thời gian dưới đây để đồng hành cùng chúng tôi qua từng chương lịch sử đầy say mê</p>
        </div>

        {/* Dynamic Buttons list */}
        <div className="flex flex-wrap items-center justify-center gap-2 max-w-lg mx-auto border-b border-stone-200 pb-6">
          {timelineEvents.map((ev) => (
            <button
              key={ev.year}
              onClick={() => setActiveTimeline(ev.year)}
              className={`px-5 py-2.5 rounded-full text-xs font-black transition-all ${
                activeTimeline === ev.year
                  ? 'bg-[#B91C1C] text-white shadow-md scale-105'
                  : 'bg-white text-stone-600 border border-stone-200 hover:bg-stone-550/10'
              }`}
            >
              Năm {ev.year}
            </button>
          ))}
        </div>

        {/* Selected content display card with animations */}
        <div className="max-w-2xl mx-auto bg-white rounded-2xl p-6 sm:p-8 border border-stone-150 shadow-sm space-y-4">
          {timelineEvents.map((ev) => {
            if (ev.year !== activeTimeline) return null;
            return (
              <div key={ev.year} className="space-y-3 animate-fadeIn">
                <div className="flex justify-between items-baseline gap-2">
                  <span className="text-3xl font-black text-[#0B3B24] font-mono select-none">
                    {ev.year}
                  </span>
                  <span className="text-[10px] uppercase font-bold text-[#B91C1C] tracking-widest font-mono">
                    {ev.subtitle}
                  </span>
                </div>
                <h4 className="text-lg font-black uppercase text-stone-900 leading-tight">
                  {ev.title}
                </h4>
                <p className="text-stone-500 text-xs sm:text-sm leading-relaxed font-medium">
                  {ev.description}
                </p>
              </div>
            );
          })}
        </div>
      </section>

      {/* 5. MEET THE GOURMET TEAMS (MEETING WORKSPACE) */}
      <section className="space-y-6" id="about-team-workspace">
        <div className="text-center max-w-lg mx-auto space-y-2">
          <span className="text-[10px] font-black uppercase tracking-wider text-[#B91C1C]">Expertise</span>
          <h3 className="text-2xl sm:text-3xl font-black italic uppercase text-[#064E3B]">Những Người Giữ Lửa</h3>
          <p className="text-stone-500 text-xs">Tìm hiểu bếp trưởng và các chuyên viên cốt cán đứng sau những thực đơn thơm ngon hoàn hảo rực cháy.</p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {teamMembers.map((member, idx) => (
            <div key={idx} className="bg-white border border-stone-250/70 rounded-3xl p-6 shadow-sm flex flex-col justify-between hover:scale-[1.01] transition-transform">
              <div className="space-y-4">
                {/* Emoji Avatar with custom accent container */}
                <div className={`w-14 h-14 rounded-2xl flex items-center justify-center text-2xl border ${member.color}`}>
                  {member.avatar}
                </div>

                <div className="space-y-1">
                  <h4 className="font-extrabold text-stone-900 text-base leading-tight">
                    {member.name}
                  </h4>
                  <span className="text-[10px] uppercase font-black text-emerald-800 tracking-wider block">
                    {member.role}
                  </span>
                </div>

                <p className="text-stone-500 text-xs leading-relaxed font-sans font-medium">
                  {member.bio}
                </p>
              </div>

              <div className="pt-4 border-t border-stone-100 flex items-center gap-1.5 text-[9px] uppercase font-bold tracking-widest text-stone-400 mt-6">
                <Clock className="w-3.5 h-3.5 text-stone-400" />
                <span>Working: Fully Bound</span>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* 6. CALL TO ACTION TO MENU/BOOKING */}
      <section className="bg-gradient-to-r from-[#064E3B] to-[#043427] text-white rounded-3xl p-8 sm:p-12 text-center space-y-6 shadow-2xl relative overflow-hidden" id="about-cta-panel">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,rgba(185,28,28,0.12),transparent_45%)]" />
        <div className="max-w-2xl mx-auto space-y-4 relative z-10">
          <h3 className="text-2xl sm:text-4xl font-black uppercase italic tracking-tight leading-none text-white">
            Đã sẵn sàng khai phá vị giác?
          </h3>
          <p className="text-white/70 text-xs sm:text-sm font-medium leading-relaxed">
            Đặt món trực tuyến để nhận ưu đãi giao hàng miễn phí, hoặc đặt bàn trực tiếp tại không gian mang phong cách Hy Lạp rực rỡ của chúng tôi ngay hôm nay!
          </p>

          <div className="flex flex-col sm:flex-row justify-center items-center gap-3 pt-4 select-none">
            <button
              onClick={() => setCurrentPage('menu')}
              className="w-full sm:w-auto px-8 py-3.5 bg-[#B91C1C] hover:bg-[#991B1B] text-white font-black uppercase text-xs tracking-wider rounded-xl transition duration-150 shadow-lg shadow-black/20 flex items-center justify-center gap-2 group cursor-pointer"
            >
              <span>Xem Ngay Thực Đơn</span>
              <ChevronRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            </button>
            <button
              onClick={() => setCurrentPage('booking')}
              className="w-full sm:w-auto px-8 py-3.5 bg-white/10 hover:bg-white/20 border border-white/25 text-white font-black uppercase text-xs tracking-wider rounded-xl transition cursor-pointer"
            >
              Đặt Bàn Trực Tiếp
            </button>
          </div>
        </div>
      </section>

    </div>
  );
}
