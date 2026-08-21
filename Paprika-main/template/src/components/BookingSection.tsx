import React, { useState } from 'react';
import { Calendar, Users, Coffee, HelpCircle, Check, MapPin, Grid } from 'lucide-react';
import { BookingDetails, BookingGuestCount } from '../types';
import { BRANCH_STORES } from '../data/menu';

export default function BookingSection() {
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [date, setDate] = useState('');
  const [time, setTime] = useState('19:30');
  const [guests, setGuests] = useState<BookingGuestCount>(4);
  const [seatingArea, setSeatingArea] = useState<'indoor' | 'terrace' | 'garden'>('indoor');
  const [branchId, setBranchId] = useState(BRANCH_STORES[0].id);
  const [notes, setNotes] = useState('');

  // Submit states
  const [submitSuccess, setSubmitSuccess] = useState<boolean>(false);
  const [reservationCode, setReservationCode] = useState('');
  const [activeTableIndex, setActiveTableIndex] = useState<number>(3); // Custom highlighted table visualizer node

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!fullName || !phone) return;

    // Generate random code
    const generatedCode = `RES-${Math.floor(10000 + Math.random() * 90000)}`;
    setReservationCode(generatedCode);
    setSubmitSuccess(true);
  };

  const selectedStoreName = BRANCH_STORES.find(s => s.id === branchId)?.name || BRANCH_STORES[0].name;

  // Render Confirmation Ticket upon reservation success
  if (submitSuccess) {
    return (
      <div className="bg-[#FDFBF7] py-16 px-4 sm:px-6 lg:px-8 max-w-xl mx-auto" id="booking-success-view">
        <div className="bg-white rounded-3xl border border-stone-200 p-8 text-center space-y-6 shadow-xl animate-fadeIn text-stone-900">
          <div className="w-16 h-16 bg-emerald-50 text-emerald-800 mx-auto rounded-full flex items-center justify-center border-2 border-emerald-300">
            <Check className="w-8 h-8 text-emerald-700 font-black animate-pulse" />
          </div>

          <div className="space-y-2">
            <span className="text-[10px] uppercase font-mono tracking-widest bg-[#064E3B] text-white px-3 py-1 rounded-full font-bold">
              Table Reserved & Confirmed
            </span>
            <h2 className="text-2xl sm:text-3xl font-black text-stone-950 tracking-tight font-sans italic uppercase">
              See you soon, {fullName.split(' ')[0]}!
            </h2>
            <p className="text-stone-500 text-sm max-w-md mx-auto">
              Your table booking code is <strong>{reservationCode}</strong>. Present this ticket to our host on arrival.
            </p>
          </div>

          {/* Recapitulation ticket */}
          <div className="bg-stone-50 rounded-2xl border border-stone-200 p-6 text-left space-y-3.5 text-xs text-stone-800">
            <div className="flex justify-between border-b border-stone-200 pb-2">
              <span className="text-stone-500">Selected Branch:</span>
              <strong className="text-stone-900 font-sans">{selectedStoreName}</strong>
            </div>
            <div className="flex justify-between border-b border-stone-200 pb-2">
              <span className="text-stone-500">Date & Booking Time:</span>
              <strong className="text-stone-900 font-mono">{date || 'Today'} at {time}</strong>
            </div>
            <div className="flex justify-between border-b border-stone-200 pb-2">
              <span className="text-stone-500">Party Headcount:</span>
              <strong className="text-stone-900 font-mono">{guests} Guests ({seatingArea.toUpperCase()})</strong>
            </div>
            <div className="flex justify-between pb-1">
              <span className="text-stone-500">Allocated Area Spot:</span>
              <strong className="text-[#B91C1C] font-bold">Premium Table #{activeTableIndex + 1}</strong>
            </div>
          </div>

          <button
            onClick={() => setSubmitSuccess(false)}
            className="w-full py-4 bg-[#064E3B] hover:bg-[#B91C1C] text-white text-sm uppercase tracking-wider font-extrabold rounded-full transition shadow-md"
          >
            Reserve Another Table
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-[#FDFBF7] text-stone-950 py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto flex-grow" id="booking-form-view">
      
      {/* Introduction Banner header */}
      <div className="text-center space-y-2 mb-12 animate-fadeIn">
        <span className="text-[#B91C1C] font-black uppercase text-xs tracking-widest block">
          Athens Dine-In Experiences
        </span>
        <h1 className="text-3xl sm:text-4xl font-black text-stone-950 tracking-tight font-sans italic uppercase">
          Reserve A Custom Table
        </h1>
        <p className="text-stone-500 text-sm max-w-lg mx-auto">
          Avoid long queues during dynamic gourmet weekends. Fully integrated with pre-ordering options, select your preferred salon and seating zones online.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        {/* LEFT COLUMN: THE INPUTS RESERVATION FORM */}
        <form onSubmit={handleSubmit} className="lg:col-span-7 bg-white p-6 sm:p-8 rounded-2xl border border-stone-200 shadow-sm space-y-6">
          <h2 className="text-xs font-black text-stone-400 uppercase tracking-widest border-b border-stone-100 pb-2 flex items-center gap-2">
            <Calendar className="w-4 h-4 text-[#B91C1C]" />
            Reservation details
          </h2>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="space-y-1">
              <label className="block text-xs font-bold text-stone-600">Full Guest Name *</label>
              <input
                type="text"
                required
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                placeholder="Constantinos Papadopoulos"
                className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
              />
            </div>
            
            <div className="space-y-1">
              <label className="block text-xs font-bold text-stone-600">Mobile Phone Number *</label>
              <input
                type="tel"
                required
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="+30 697 990 8822"
                className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="space-y-1">
              <label className="block text-xs font-bold text-stone-600">Email Address</label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="constantinos@gmail.com"
                className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
              />
            </div>

            <div className="space-y-1">
              <label className="block text-xs font-bold text-stone-600">Select Athens Branch</label>
              <select
                value={branchId}
                onChange={(e) => setBranchId(e.target.value)}
                className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-3 py-3 text-sm outline-none transition"
              >
                {BRANCH_STORES.map((store) => (
                  <option key={store.id} value={store.id}>
                    {store.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="grid grid-cols-3 gap-3">
            <div className="space-y-1 col-span-2">
              <label className="block text-xs font-bold text-stone-600">Reservation Date</label>
              <input
                type="date"
                value={date}
                onChange={(e) => setDate(e.target.value)}
                className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-2.5 text-sm outline-none transition"
              />
            </div>

            <div className="space-y-1">
              <label className="block text-xs font-bold text-stone-600">Time Arrival</label>
              <input
                type="text"
                value={time}
                onChange={(e) => setTime(e.target.value)}
                placeholder="19:30"
                className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
              />
            </div>
          </div>

          {/* GUESTS COUNTER SELECT BUTTONS */}
          <div className="space-y-2">
            <label className="block text-xs font-bold text-stone-600">Party Headcount (Number of Guests)</label>
            <div className="grid grid-cols-4 sm:grid-cols-8 gap-2">
              {([1, 2, 3, 4, 5, 6, 8, 10] as BookingGuestCount[]).map((num) => (
                <button
                  key={num}
                  type="button"
                  onClick={() => setGuests(num)}
                  className={`py-2 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1 ${
                    guests === num
                      ? 'bg-[#064E3B] border-[#064E3B] text-white'
                      : 'bg-stone-50 border-stone-200 text-stone-700 hover:bg-stone-100'
                  }`}
                >
                  <Users className="w-3.5 h-3.5 shrink-0" />
                  {num}
                </button>
              ))}
            </div>
          </div>

          {/* SEATING ZONE CARD BUTTONS */}
          <div className="space-y-2">
            <label className="block text-xs font-bold text-stone-600">Preferred Seating Spot Location Area</label>
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              {[
                { id: 'indoor', name: 'Indoor Cosy Saloon', desc: 'Air-conditioned premium space' },
                { id: 'terrace', name: 'Covered Terrace Railings', desc: 'Overlooking Athens central views' },
                { id: 'garden', name: 'Open Air Pergola Garden', desc: 'Fresh mint scent location' }
              ].map((area) => (
                <button
                  key={area.id}
                  type="button"
                  onClick={() => {
                    setSeatingArea(area.id as any);
                    setActiveTableIndex(area.id === 'indoor' ? 3 : area.id === 'terrace' ? 6 : 9);
                  }}
                  className={`p-4 rounded-xl border text-left transition flex flex-col justify-between h-full ${
                    seatingArea === area.id
                      ? 'bg-[#064E3B]/5 border-[#064E3B] text-stone-950 border-2'
                      : 'bg-stone-50 border-stone-200 text-stone-600 hover:bg-stone-100'
                  }`}
                >
                  <span className="block font-black text-xs uppercase tracking-wide">{area.name}</span>
                  <span className="block text-[10px] text-stone-400 mt-1">{area.desc}</span>
                </button>
              ))}
            </div>
          </div>

          <div className="space-y-1">
            <label className="block text-xs font-bold text-stone-600">Custom Dietary Requests / Special Notes</label>
            <textarea
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Allergen details (e.g. peanut or gluten free guidelines). High chairs requested for kids, etc."
              rows={3}
              className="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-xl px-4 py-3 text-sm outline-none transition"
            />
          </div>

          <button
            type="submit"
            className="w-full py-4 bg-[#B91C1C] hover:bg-[#991B1B] text-white text-xs uppercase tracking-widest font-black rounded-full transition duration-150 shadow-md"
          >
            Confirm Reservation Table
          </button>
        </form>

        {/* RIGHT COLUMN: FLOOR MAP */}
        <div className="lg:col-span-5 space-y-6 lg:sticky lg:top-28">
          <div className="bg-white rounded-3xl border border-stone-200 p-6 shadow-sm space-y-6">
            <div className="space-y-1.5 pb-3 border-b border-stone-100">
              <h3 className="text-sm font-extrabold text-stone-900 uppercase tracking-wide flex items-center gap-1.5 font-heading">
                <Grid className="w-4 h-4 text-[#B91C1C]" />
                Interactive Seat Map
              </h3>
              <p className="text-stone-500 text-xs leading-relaxed">
                Allocated seating preview for <strong>{guests} guests</strong> in the <strong>{seatingArea.toUpperCase()}</strong> spot. Click on a table node to change spots.
              </p>
            </div>

            {/* SEATING GRID LAYOUT MOCKUP */}
            <div className="p-4 bg-[#043427] rounded-2xl border border-emerald-950 space-y-4 shadow-inner">
              <div className="h-6 w-full bg-[#064E3B] text-center rounded text-[10px] font-mono text-stone-200 flex items-center justify-center border border-white/5">
                HOST RECEPTION & ENTRANCE BAR
              </div>

              {/* Physical circular and rectangular CSS tables */}
              <div className="grid grid-cols-4 gap-4 py-6 justify-center text-center">
                {Array.from({ length: 12 }).map((_, idx) => {
                  const isCurrent = activeTableIndex === idx;
                  const isReserved = (idx * 7) % 3 === 0 && !isCurrent; // Seed mock reserved tables
                  return (
                    <div
                      key={idx}
                      onClick={() => !isReserved && setActiveTableIndex(idx)}
                      className={`h-11 rounded-lg flex flex-col items-center justify-center text-[10px] font-bold cursor-pointer transition uppercase ${
                        isCurrent
                          ? 'bg-[#B91C1C] text-white shadow-lg ring-2 ring-white scale-105 border border-white'
                          : isReserved
                          ? 'bg-stone-800 text-stone-500 cursor-not-allowed border border-stone-900'
                          : 'bg-[#064E3B] text-white/90 hover:bg-white/10 hover:text-white border border-white/10'
                      }`}
                    >
                      <span className="block leading-none text-[8px] opacity-70 font-mono">T-{idx + 1}</span>
                      <span className="block leading-none mt-1">
                        {isCurrent ? `YOU` : isReserved ? 'OCC' : `SEAT`}
                      </span>
                    </div>
                  );
                })}
              </div>

              {/* Map legend */}
              <div className="flex justify-around text-[10px] text-white/70 border-t border-white/10 pt-3">
                <div className="flex items-center gap-1.5">
                  <span className="w-2.5 h-2.5 rounded bg-[#B91C1C]" />
                  <span>My Table</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <span className="w-2.5 h-2.5 rounded bg-[#064E3B]" />
                  <span>Available</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <span className="w-2.5 h-2.5 rounded bg-stone-800" />
                  <span>Reserved</span>
                </div>
              </div>
            </div>

            {/* Quick validation coordinates trust box */}
            <div className="p-4 bg-amber-500/5 rounded-xl border border-amber-500/20 text-xs text-amber-900 flex gap-2.5">
              <Coffee className="w-5 h-5 text-[#FFD700] shrink-0 mt-0.5 animate-bounce" />
              <div className="space-y-1">
                <strong>Table Hold Policy:</strong>
                <p className="text-amber-800 leading-normal text-[11px]">
                  We hold your booked Table #{activeTableIndex + 1} for up to <strong>15 minutes</strong> after the arrival time {time} before reallocating. No fee holds.
                </p>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  );
}
