package com.tesourariacme.api.infrastructure;

import com.tesourariacme.api.domain.Member;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.Optional;

@Repository
public interface MemberRepository extends JpaRepository<Member, Long> {
    Optional<Member> findByNameIgnoreCase(String name);
}
