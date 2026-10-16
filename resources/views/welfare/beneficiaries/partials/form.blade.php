<div class="row g-4">

    {{-- Basic Identity --}}
    <div class="col-12">
        <div class="section-title">Basic Identity</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">
            Full Name <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            name="full_name"
            class="form-control @error('full_name') is-invalid @enderror"
            value="{{ old('full_name', $beneficiary->full_name ?? '') }}"
            required
        >

        @error('full_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Father / Husband Name</label>

        <input
            type="text"
            name="father_husband_name"
            class="form-control @error('father_husband_name') is-invalid @enderror"
            value="{{ old('father_husband_name', $beneficiary->father_husband_name ?? '') }}"
        >

        @error('father_husband_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">CNIC</label>

        <input
            type="text"
            name="cnic"
            class="form-control @error('cnic') is-invalid @enderror"
            value="{{ old('cnic', $beneficiary->cnic ?? '') }}"
            placeholder="12345-1234567-1"
        >

        @error('cnic')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Phone</label>

        <input
            type="text"
            name="phone"
            class="form-control @error('phone') is-invalid @enderror"
            value="{{ old('phone', $beneficiary->phone ?? '') }}"
        >

        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Alternate Phone</label>

        <input
            type="text"
            name="alternate_phone"
            class="form-control @error('alternate_phone') is-invalid @enderror"
            value="{{ old('alternate_phone', $beneficiary->alternate_phone ?? '') }}"
        >

        @error('alternate_phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label class="form-label">Address</label>

        <textarea
            name="address"
            rows="3"
            class="form-control @error('address') is-invalid @enderror"
        >{{ old('address', $beneficiary->address ?? '') }}</textarea>

        @error('address')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Location / Area</label>

        <input
            type="text"
            name="location"
            class="form-control @error('location') is-invalid @enderror"
            value="{{ old('location', $beneficiary->location ?? '') }}"
        >

        @error('location')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Classification --}}
    <div class="col-12 mt-2">
        <div class="section-title">Beneficiary Classification</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">
            Category <span class="text-danger">*</span>
        </label>

        @php
            $selectedCategory = old('category', $beneficiary->category ?? '');
        @endphp

        <select
            name="category"
            class="form-select @error('category') is-invalid @enderror"
            required
        >
            <option value="">Select category</option>

            <option value="elderly_poor"
                {{ $selectedCategory === 'elderly_poor' ? 'selected' : '' }}>
                Elderly / Extremely Poor
            </option>

            <option value="disabled_vulnerable"
                {{ $selectedCategory === 'disabled_vulnerable' ? 'selected' : '' }}>
                Disabled / Vulnerable
            </option>

            <option value="poor_employable"
                {{ $selectedCategory === 'poor_employable' ? 'selected' : '' }}>
                Poor but Employable
            </option>

            <option value="student"
                {{ $selectedCategory === 'student' ? 'selected' : '' }}>
                Students / Education Support
            </option>

            <option value="emergency"
                {{ $selectedCategory === 'emergency' ? 'selected' : '' }}>
                Emergency Assistance
            </option>
        </select>

        @error('category')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Marital Status</label>

        @php
            $selectedMarital = old('marital_status', $beneficiary->marital_status ?? '');
        @endphp

        <select name="marital_status" class="form-select">
            <option value="">Select</option>
            <option value="single" {{ $selectedMarital === 'single' ? 'selected' : '' }}>Single</option>
            <option value="married" {{ $selectedMarital === 'married' ? 'selected' : '' }}>Married</option>
            <option value="widowed" {{ $selectedMarital === 'widowed' ? 'selected' : '' }}>Widowed</option>
            <option value="divorced" {{ $selectedMarital === 'divorced' ? 'selected' : '' }}>Divorced</option>
            <option value="separated" {{ $selectedMarital === 'separated' ? 'selected' : '' }}>Separated</option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Dependents</label>

        <input
            type="number"
            name="dependents_count"
            min="0"
            max="100"
            class="form-control @error('dependents_count') is-invalid @enderror"
            value="{{ old('dependents_count', $beneficiary->dependents_count ?? 0) }}"
        >

        @error('dependents_count')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Financial Condition --}}
    <div class="col-12 mt-2">
        <div class="section-title">Family & Financial Condition</div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Occupation</label>

        <input
            type="text"
            name="occupation"
            class="form-control @error('occupation') is-invalid @enderror"
            value="{{ old('occupation', $beneficiary->occupation ?? '') }}"
        >

        @error('occupation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Monthly Income</label>

        <div class="input-group">
            <span class="input-group-text">PKR</span>

            <input
                type="number"
                name="monthly_income"
                min="0"
                step="0.01"
                class="form-control @error('monthly_income') is-invalid @enderror"
                value="{{ old('monthly_income', $beneficiary->monthly_income ?? '') }}"
            >
        </div>

        @error('monthly_income')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Assets Description</label>

        <textarea
            name="assets_description"
            rows="4"
            class="form-control"
        >{{ old('assets_description', $beneficiary->assets_description ?? '') }}</textarea>
    </div>

    <div class="col-md-6">
        <label class="form-label">Debts / Liabilities</label>

        <textarea
            name="debts_description"
            rows="4"
            class="form-control"
        >{{ old('debts_description', $beneficiary->debts_description ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-label">Household Condition</label>

        <textarea
            name="household_condition"
            rows="4"
            class="form-control"
        >{{ old('household_condition', $beneficiary->household_condition ?? '') }}</textarea>
    </div>

    {{-- Review --}}
    <div class="col-12 mt-2">
        <div class="section-title">Review & Notes</div>
    </div>

    <div class="col-md-4">
        <label class="form-label">Next Review Date</label>

        <input
            type="date"
            name="next_review_date"
            class="form-control @error('next_review_date') is-invalid @enderror"
            value="{{ old(
                'next_review_date',
                isset($beneficiary) && $beneficiary->next_review_date
                    ? $beneficiary->next_review_date->format('Y-m-d')
                    : ''
            ) }}"
        >

        @error('next_review_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Notes</label>

        <textarea
            name="notes"
            rows="4"
            class="form-control"
        >{{ old('notes', $beneficiary->notes ?? '') }}</textarea>
    </div>

</div>